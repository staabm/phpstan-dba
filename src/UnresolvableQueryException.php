<?php

declare(strict_types=1);

namespace staabm\PHPStanDba;

/**
 * @api
 */
abstract class UnresolvableQueryException extends DbaException
{
    abstract public static function getTip(): string;

    public function asRuleMessage(): string
    {
        return 'Unresolvable Query: ' . $this->getMessage() . '.';
    }
}
