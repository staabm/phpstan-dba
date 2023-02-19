<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SqlAst;

use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use SqlFtw\Sql\Expression\BoolValue;
use SqlFtw\Sql\Expression\CaseExpression;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Expression\FunctionCall;
use SqlFtw\Sql\Expression\Identifier;
use SqlFtw\Sql\Expression\IntValue;
use SqlFtw\Sql\Expression\Literal;
use SqlFtw\Sql\Expression\NullLiteral;
use SqlFtw\Sql\Expression\NumericValue;
use SqlFtw\Sql\Expression\SimpleName;
use SqlFtw\Sql\Expression\StringValue;
use staabm\PHPStanDba\SchemaReflection\Table;

final class QueryScope
{
    /**
     * @var list<QueryFunctionReturnTypeExtension>
     */
    private $extensions;

    /**
     * @var Table
     */
    private $fromTable;

    /**
     * @var list<Table>
     */
    private $joinedTables;

    /**
     * @param list<Table> $joinedTables
     */
    public function __construct(Table $fromTable, array $joinedTables)
    {
        $this->fromTable = $fromTable;
        $this->joinedTables = $joinedTables;

        $this->extensions = [
            new PositiveIntReturnTypeExtension(),
            new CoalesceReturnTypeExtension(),
            new IfNullReturnTypeExtension(),
            new IfReturnTypeExtension(),
            new ConcatReturnTypeExtension(),
            new InstrReturnTypeExtension(),
            new StrCaseReturnTypeExtension(),
            new AvgReturnTypeExtension(),
            new SumReturnTypeExtension(),
            new IsNullReturnTypeExtension(),
            new AbsReturnTypeExtension(),
            new RoundReturnTypeExtension(),
        ];
    }

    /**
     * @param Identifier|Literal|ExpressionNode $expression
     */
    public function getType($expression): Type
    {
        if ($expression instanceof NullLiteral) {
            return new NullType();
        }
        if ($expression instanceof StringValue) {
            return new ConstantStringType($expression->asString());
        }
        if ($expression instanceof IntValue) {
            return new ConstantIntegerType($expression->asInt());
        }
        if ($expression instanceof BoolValue) {
            $asBool = $expression->asBool();
            if (null === $asBool) {
                return new NullType();
            }

            return new ConstantBooleanType($asBool);
        }
        if ($expression instanceof NumericValue) {
            $number = $expression->asNumber();
            if (\is_int($number)) {
                return new ConstantIntegerType($number);
            }

            return new ConstantFloatType($number);
        }

        if ($expression instanceof SimpleName) {
            foreach ($this->fromTable->getColumns() as $column) {
                if ($column->getName() === $expression->getName()) {
                    return $column->getType();
                }
            }

            foreach ($this->joinedTables as $joinedTable) {
                foreach ($joinedTable->getColumns() as $column) {
                    if ($column->getName() === $expression->getName()) {
                        return $column->getType();
                    }
                }
            }

            throw new ShouldNotHappenException('Unable to resolve column ' . $expression->getName());
        }

        if ($expression instanceof CaseExpression) {
            $resultTypes = [];
            foreach ($expression->getResults() as $result) {
                $resultTypes[] = $this->getType($result);
            }

            return TypeCombinator::union(...$resultTypes);
        }

        if ($expression instanceof FunctionCall) {
            foreach ($this->extensions as $extension) {
                if (! $extension->isFunctionSupported($expression)) {
                    continue;
                }

                $extensionType = $extension->getReturnType($expression, $this);
                if (null !== $extensionType) {
                    return $extensionType;
                }
            }
        }

        return new MixedType();
    }
}
