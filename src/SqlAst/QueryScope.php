<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SqlAst;

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
use SqlFtw\Sql\Expression\ComparisonOperator;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Expression\FunctionCall;
use SqlFtw\Sql\Expression\Identifier;
use SqlFtw\Sql\Expression\IntValue;
use SqlFtw\Sql\Expression\Literal;
use SqlFtw\Sql\Expression\NullLiteral;
use SqlFtw\Sql\Expression\NumericValue;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Expression\Parentheses;
use SqlFtw\Sql\Expression\SimpleName;
use SqlFtw\Sql\Expression\StringValue;
use staabm\PHPStanDba\SchemaReflection\Column;
use staabm\PHPStanDba\SchemaReflection\Join;
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
     * @var list<Join>
     */
    private $joinedTables;

    /**
     * @param list<Join> $joinedTables
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
            new ReplaceReturnTypeExtension(),
            new AvgReturnTypeExtension(),
            new SumReturnTypeExtension(),
            new IsNullReturnTypeExtension(),
            new AbsReturnTypeExtension(),
            new RoundReturnTypeExtension(),
            new MinMaxReturnTypeExtension(),
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
            $resolvedType = $this->resolveSimpleName($expression, true);
            if ($resolvedType !== null) {
                return $resolvedType;
            }

            return new MixedType();
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

    private function resolveSimpleName(SimpleName $expression, bool $narrowJoinTypes): ?Type
    {
        foreach ($this->fromTable->getColumns() as $column) {
            if ($column->getName() === $expression->getName()) {
                return $column->getType();
            }
        }

        foreach ($this->joinedTables as $join) {
            $joinedTable = $join->getTable();

            foreach ($joinedTable->getColumns() as $column) {
                if ($column->getName() !== $expression->getName()) {
                    continue;
                }

                if ($narrowJoinTypes) {
                    return $this->narrowJoinedColumnType($column, $join);
                }
                return $column->getType();
            }
        }

        return null;
    }

    private function narrowJoinedColumnType(Column $column, Join $join): ?Type
    {
        $columnType = $column->getType();
        if ($join->getJoinType() === Join::TYPE_INNER) {
            $columnType = $this->narrowJoinCondition($column, $join);
            if ($columnType !== null) {
                return TypeCombinator::removeNull($columnType);
            }
        }

        if ($join->getJoinType() === Join::TYPE_OUTER) {
            $columnType = $this->narrowJoinCondition($column, $join);
            if ($columnType !== null) {
                return TypeCombinator::addNull($columnType);
            }
        }
        return $columnType;
    }

    private function narrowJoinCondition(Column $column, Join $join): ?Type
    {
        $joinCondition = $join->getJoinCondition();
        while ($joinCondition instanceof Parentheses) {
            $joinCondition = $joinCondition->getContents();
        }

        if (! $joinCondition instanceof ComparisonOperator) {
            return null;
        }

        if ($joinCondition->getOperator()->getValue() !== Operator::EQUAL) {
            return null;
        }

        if (! $joinCondition->getLeft() instanceof SimpleName ||
            ! $joinCondition->getRight() instanceof SimpleName) {
            return null;
        }

        $leftName = ParserInference::getIdentifierName($joinCondition->getLeft());
        $rightName = ParserInference::getIdentifierName($joinCondition->getRight());
        if ($leftName === $column->getName() || $rightName === $column->getName()) {
            $leftType = $this->resolveSimpleName($joinCondition->getLeft(), false);
            $rightType = $this->resolveSimpleName($joinCondition->getRight(), false);

            if ($leftType === null || $rightType === null) {
                return null;
            }

            return TypeCombinator::intersect($leftType, $rightType);
        }

        return null;
    }
}
