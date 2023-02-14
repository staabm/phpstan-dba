<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\ParserExtension;

use PHPStan\ShouldNotHappenException;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Expression\Identifier;
use SqlFtw\Sql\Expression\Literal;
use SqlFtw\Sql\Expression\NullLiteral;
use SqlFtw\Sql\Expression\SimpleName;
use staabm\PHPStanDba\SchemaReflection\Table;

final class QueryScope
{
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
    }

    /**
     * @param Identifier|Literal|ExpressionNode $expression
     */
    public function getType($expression): Type
    {
        if ($expression instanceof NullLiteral) {
            return new NullType();
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

        return new MixedType();
    }
}
