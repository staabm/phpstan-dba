<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use mysqli_result;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectShapeType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use staabm\PHPStanDba\MysqliReflection\MysqliResultObjectType;

final class MysqliResultDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension, DynamicMethodReturnTypeExtension
{
    // same-value replacements for MYSQLI_ASSOC, MYSQLI_NUM, MYSQLI_BOTH constants in mysqli extension
    private const MYSQLI_ASSOC = 1;

    private const MYSQLI_NUM = 2;

    private const MYSQLI_BOTH = 3;

    private ReflectionProvider $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getClass(): string
    {
        return mysqli_result::class;
    }

    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return \in_array(
            strtolower($functionReflection->getName()),
            ['mysqli_fetch_assoc', 'mysqli_fetch_column', 'mysqli_fetch_object', 'mysqli_fetch_row'],
            true
        );
    }

    public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
    {
        $args = $functionCall->getArgs();
        $name = strtolower($functionReflection->getName());

        // mysqli_fetch_ functions all have at least 1 arg: the mysqli_result
        switch ($name) {
            case 'mysqli_fetch_assoc': // $result
            case 'mysqli_fetch_row':   // $result
                if (\count($args) !== 1) {
                    return null;
                }
                break;
            case 'mysqli_fetch_object': // $result, $class = 'stdClass', $constructor = []
                if (\count($args) < 1 || \count($args) > 3) {
                    return null;
                }
                break;
            case 'mysqli_fetch_column': // $result, $column = 0; added in PHP 8.1
                $phpVersion = $scope->getPhpVersion()->getType();
                if (\count($args) < 1 || \count($args) > 2 ||
                    IntegerRangeType::fromInterval(80100, null)->isSuperTypeOf($phpVersion)->no()) {
                    return null;
                }
                break;
        }

        $rowType = $this->getRowType($scope->getType($args[0]->value));
        if ($rowType === null) {
            return null;
        }

        // The return type is sealed array w. SQL fields as elements|null|false
        if ($name === 'mysqli_fetch_assoc') {
            return TypeCombinator::union(
                $rowType,
                new NullType(),
                new ConstantBooleanType(false)
            );
        }


        // The return type is stdClass w. SQL fields as properties|null|false
        if ($name === 'mysqli_fetch_object') {
            // arg[1] is string $class = "stdClass"
            $classNameType = $this->getArgOrDefault($functionReflection, $functionCall, $scope, 1);
            $classType = $this->getObjectTypeFromName($scope, $classNameType);
            if ($classType === null) {
                return null;
            }

            $objectTypes = [];
            foreach ($rowType->getConstantArrays() as $assocRow) {
                $properties = [];
                foreach ($assocRow->getKeyTypes() as $index => $keyType) {
                    // Only keep string column names
                    $columnNames = $keyType->getConstantStrings();
                    foreach ($columnNames as $columnName) {
                        $properties[$columnName->getValue()] = $assocRow->getValueTypes()[$index];
                    }
                }

                $objectTypes[] = TypeCombinator::intersect(
                    $classType,
                    new ObjectShapeType($properties, [])
                );
            }

            if ($objectTypes === []) {
                return null;
            }

            return TypeCombinator::union(
                TypeCombinator::union(...$objectTypes),
                new NullType(),
                new ConstantBooleanType(false)
            );
        }

        $numericRowType = $this->getRowTypeForMode($rowType, self::MYSQLI_NUM);
        if ($numericRowType === null) {
            return null;
        }

        // The return type is enumerated array of SQL fields|null|false
        if ($name === 'mysqli_fetch_row') {
            return TypeCombinator::union(
                $numericRowType,
                new NullType(),
                new ConstantBooleanType(false)
            );
        }

        // The return type is a single SQL field|false
        if ($name === 'mysqli_fetch_column') {
            $columnTypes = [];
            if (count($args) === 1 || count($args) === 2) {
                // arg[1] is int $column = 0
                $offsetType = $this->getArgOrDefault($functionReflection, $functionCall, $scope, 1);
                // * column arg is a constant OR
                // * column arg may be one of several possible constants
                //   Occurs with, eg, column=rand() ? 0 : 1  OR
                // * column arg may be one of several possible constants
                //   Occurs in, eg, a for loop
                // If any of the types are non-integral, it isn't a valid
                // call to mysqli_fetch_column
                foreach ($offsetType->getFiniteTypes() as $type) {
                    if (! $type instanceof ConstantIntegerType) {
                        return null;
                    }
                    foreach ($numericRowType->getConstantArrays() as $numericRow) {
                        $columnTypes[] =
                            $numericRow->hasOffsetValueType($offsetType)->yes()
                            ? $numericRow->getOffsetValueType($offsetType)
                            : new ConstantBooleanType(false);
                    }
                }
            } else {
                return null;
            }

            if ($columnTypes === []) {
                return null;
            }

            // The ...|false on DB connection failure
            $columnTypes[] = new ConstantBooleanType(false);
            return TypeCombinator::union(...$columnTypes);
        }

        return null;
    }

    private function getArgOrDefault(FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope, int $i): Type
    {
        $args = $funcCall->getArgs();
        if (\count($args) > $i) {
            return $scope->getType($args[$i]->value);
        }
        $variant = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $args,
            $functionReflection->getVariants()
        );
        $default = $variant->getParameters()[$i]->getDefaultValue();
        if ($default === null) {
            throw new ShouldNotHappenException("Argument $i does not have a default");
        }
        return $default;
    }

    private function getObjectTypeFromName(Scope $scope, Type $name): ?Type
    {
        $objectTypes = [];
        foreach ($name->getConstantStrings() as $type) {
            $className = $type->getValue();

            // Does PHPStan know about the class?
            if (! $this->reflectionProvider->hasClass($className)) {
                return null;
            }
            // Get its fully qualified name
            $className = $this->reflectionProvider->getClass($className)->getName();
            $objectTypes[] = new ObjectType($className);
        }
        return TypeCombinator::union(...$objectTypes);
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return \in_array(strtolower($methodReflection->getName()), ['fetch_assoc', 'fetch_all'], true);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
    {
        $resultType = $scope->getType($methodCall->var);
        $methodName = strtolower($methodReflection->getName());
        if ('fetch_assoc' === $methodName) {
            if (count($methodCall->getArgs()) === 0) {
                $rowType = $this->getRowType($resultType);
                if ($rowType !== null) {
                    return TypeCombinator::union(
                        $rowType,
                        new NullType(),
                        new ConstantBooleanType(false)
                    );
                }
            }

            return null;
        }

        $modes = [self::MYSQLI_NUM];
        if (count($methodCall->getArgs()) > 0) {
            $modes = $this->getModes($scope->getType($methodCall->getArgs()[0]->value));
            if ($modes === null) {
                return null;
            }
        }

        $rowType = $this->getRowType($resultType);
        if ($rowType === null) {
            return null;
        }

        $rows = [];
        foreach ($modes as $mode) {
            $modeRowType = $this->getRowTypeForMode($rowType, $mode);
            if ($modeRowType === null) {
                return null;
            }

            $rows[] = $modeRowType;
        }

        return TypeCombinator::intersect(
            new ArrayType(new IntegerType(), TypeCombinator::union(...$rows)),
            new AccessoryArrayListType()
        );
    }

    private function getRowType(Type $resultType): ?Type
    {
        if ($resultType instanceof MysqliResultObjectType) {
            return $resultType->getRowType();
        }

        if (! $resultType instanceof UnionType) {
            return null;
        }

        $rowTypes = [];
        foreach ($resultType->getTypes() as $type) {
            $rowType = $this->getRowType($type);
            if ($rowType === null) {
                return null;
            }

            $rowTypes[] = $rowType;
        }

        return TypeCombinator::union(...$rowTypes);
    }

    /**
     * @return list<int>|null
     */
    private function getModes(Type $modeType): ?array
    {
        if ($modeType instanceof ConstantIntegerType) {
            return [$modeType->getValue()];
        }

        if (! $modeType instanceof UnionType) {
            return null;
        }

        $modes = [];
        foreach ($modeType->getTypes() as $type) {
            $typeModes = $this->getModes($type);
            if ($typeModes === null) {
                return null;
            }

            foreach ($typeModes as $mode) {
                $modes[] = $mode;
            }
        }

        return $modes;
    }

    private function getRowTypeForMode(Type $rowType, int $mode): ?Type
    {
        if (! \in_array($mode, [self::MYSQLI_NUM, self::MYSQLI_ASSOC, self::MYSQLI_BOTH], true)) {
            return null;
        }

        $rowTypes = [];
        foreach ($rowType->getConstantArrays() as $arrayType) {
            $rowTypes[] = $this->convertRowType($arrayType, $mode);
        }

        if (count($rowTypes) === 0) {
            return null;
        }

        return TypeCombinator::union(...$rowTypes);
    }

    private function convertRowType(ConstantArrayType $rowType, int $mode): Type
    {
        $builder = ConstantArrayTypeBuilder::createEmpty();
        $numericIndex = 0;

        foreach ($rowType->getKeyTypes() as $index => $keyType) {
            $valueType = $rowType->getValueTypes()[$index];
            if ($mode === self::MYSQLI_ASSOC || $mode === self::MYSQLI_BOTH) {
                $builder->setOffsetValueType($keyType, $valueType);
            }

            if ($mode === self::MYSQLI_NUM || $mode === self::MYSQLI_BOTH) {
                $builder->setOffsetValueType(new ConstantIntegerType($numericIndex), $valueType);
                $numericIndex++;
            }
        }

        return $builder->getArray();
    }
}
