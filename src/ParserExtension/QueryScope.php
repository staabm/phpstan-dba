<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\ParserExtension;

use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use SqlFtw\Sql\Expression\BoolValue;
use SqlFtw\Sql\Expression\ExpressionNode;
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
     * @var list<QueryExpressionReturnTypeExtension>
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
            new PositiveIntReturningReturnTypeExtension(),
            new CoalesceReturnTypeExtension(),
            new ConcatReturnTypeExtension(),
            new InstrReturnTypeExtension(),
            new StrCaseReturnTypeExtension(),
            new AvgReturnTypeExtension(),
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

            throw new ShouldNotHappenException('Unable to resolve column '.$expression->getName());
        }

        foreach ($this->extensions as $extension) {
            if (!$extension->isExpressionSupported($expression)) {
                continue;
            }

            $extensionType = $extension->getTypeFromExpression($expression, $this);
            if (null !== $extensionType) {
                return $extensionType;
            }
        }

        return new MixedType();
    }
}
