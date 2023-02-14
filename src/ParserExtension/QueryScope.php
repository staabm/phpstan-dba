<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\ParserExtension;

use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use SqlFtw\Sql\Expression\Identifier;
use SqlFtw\Sql\Expression\Literal;
use SqlFtw\Sql\Expression\NullLiteral;
use SqlFtw\Sql\Expression\SimpleName;
use staabm\PHPStanDba\Types\MysqlIntegerRanges;

final class QueryScope
{
    /**
     * @param Identifier|Literal $expression
     */
    public function getType($expression): Type
    {
        if ($expression instanceof NullLiteral) {
            return new NullType();
        }

        if ($expression instanceof SimpleName) {
            // XXX utilize schema reflection

            if ('eladaid' === $expression->getName()) {
                $ranges = new MysqlIntegerRanges();

                return TypeCombinator::addNull($ranges->unsignedTinyInt());
            }
            if ('akid' === $expression->getName()) {
                $ranges = new MysqlIntegerRanges();

                return $ranges->signedInt();
            }
        }

        return new MixedType();
    }
}
