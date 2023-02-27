<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;
use staabm\PHPStanDba\QueryReflection\QueryReflection;

/**
 * @implements Rule<CallLike>
 *
 * @see DoctrineKeyValueStyleRuleTest
 */
final class DoctrineKeyValueStyleRule implements Rule
{
    /**
     * @var array<array{string, string, list<int>}>
     */
    private $classMethods;

    /**
     * @param list<string> $classMethods
     */
    public function __construct(array $classMethods)
    {
        $this->classMethods = [];
        foreach ($classMethods as $classMethod) {
            sscanf($classMethod, '%[^::]::%[^#]#%[0-9,]', $className, $methodName, $arrayArgPositions);
            if (! \is_string($className) || ! \is_string($methodName)) {
                throw new ShouldNotHappenException('Invalid classMethod definition');
            }
            if ($arrayArgPositions !== null) {
                $arrayArgPositions = array_map('intval', explode(',', strval($arrayArgPositions)));
            } else {
                $arrayArgPositions = [];
            }
            $this->classMethods[] = [$className, $methodName, $arrayArgPositions];
        }
    }

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    /**
     * @return RuleError[]
     */
    public function processNode(Node $callLike, Scope $scope): array
    {
        if ($callLike instanceof MethodCall) {
            if (! $callLike->name instanceof Node\Identifier) {
                return [];
            }

            $methodReflection = $scope->getMethodReflection($scope->getType($callLike->var), $callLike->name->toString());
        } elseif ($callLike instanceof New_) {
            if (! $callLike->class instanceof FullyQualified) {
                return [];
            }
            $methodReflection = $scope->getMethodReflection(new ObjectType($callLike->class->toCodeString()), '__construct');
        } else {
            return [];
        }

        if (null === $methodReflection) {
            return [];
        }

        $unsupportedMethod = true;
        $arrayArgPositions = [];
        foreach ($this->classMethods as [$className, $methodName, $arrayArgPositionsConfig]) {
            if ($methodName === $methodReflection->getName() &&
                ($methodReflection->getDeclaringClass()->getName() === $className || $methodReflection->getDeclaringClass()->isSubclassOf($className))) {
                $arrayArgPositions = $arrayArgPositionsConfig;
                $unsupportedMethod = false;
                break;
            }
        }

        if ($unsupportedMethod) {
            return [];
        }

        $args = $callLike->getArgs();

        if (\count($args) < 1) {
            return [];
        }

        $tableExpr = $args[0]->value;
        $tableType = $scope->getType($tableExpr);
        if (! $tableType instanceof ConstantStringType) {
            return [
                RuleErrorBuilder::message('Argument #0 expects a literal string, got ' . $tableType->describe(VerbosityLevel::precise()))->line($callLike->getLine())->build(),
            ];
        }

        $queryReflection = new QueryReflection();
        $schemaReflection = $queryReflection->getSchemaReflection();

        // Table name may be escaped with backticks
        $argTableName = trim($tableType->getValue(), '`');
        $table = $schemaReflection->getTable($argTableName);
        if (null === $table) {
            return [
                RuleErrorBuilder::message('Query error: Table "' . $argTableName . '" does not exist')->line($callLike->getLine())->build(),
            ];
        }

        // All array arguments should have table columns as keys
        $errors = [];
        foreach ($arrayArgPositions as $arrayArgPosition) {
            // If the argument doesn't exist, just skip it since we don't want
            // to error in case it has a default value
            if (! \array_key_exists($arrayArgPosition, $args)) {
                continue;
            }

            $argType = $scope->getType($args[$arrayArgPosition]->value);
            if (! $argType instanceof ConstantArrayType) {
                $errors[] = 'Argument #' . $arrayArgPosition . ' is not a constant array, got ' . $argType->describe(VerbosityLevel::precise());
                continue;
            }

            foreach ($argType->getKeyTypes() as $keyIndex => $keyType) {
                if (! $keyType instanceof ConstantStringType) {
                    $errors[] = 'Element #' . $keyIndex . ' of argument #' . $arrayArgPosition . ' must have a string key, got ' . $keyType->describe(VerbosityLevel::precise());
                    continue;
                }

                // Column name may be escaped with backticks
                $argColumnName = trim($keyType->getValue(), '`');

                $argColumn = null;
                foreach ($table->getColumns() as $column) {
                    if ($argColumnName === $column->getName()) {
                        $argColumn = $column;
                    }
                }
                if (null === $argColumn) {
                    $errors[] = 'Column "' . $table->getName() . '.' . $argColumnName . '" does not exist';
                    continue;
                }

                // Be a bit generous here by allowing column value types that
                // are *maybe* accepted. If we want to be strict, we can warn
                // unless they are *definitely* accepted.
                $valueType = $argType->getValueTypes()[$keyIndex];
                if ($argColumn->getType()->accepts($valueType, true)->no()) {
                    $errors[] = 'Column "' . $table->getName() . '.' . $argColumnName . '" expects value type ' . $argColumn->getType()->describe(VerbosityLevel::precise()) . ', got type ' . $valueType->describe(VerbosityLevel::precise());
                }
            }
        }

        $ruleErrors = [];
        foreach ($errors as $error) {
            $ruleErrors[] = RuleErrorBuilder::message('Query error: ' . $error)->line($callLike->getLine())->build();
        }
        return $ruleErrors;
    }
}
