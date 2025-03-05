<?php

declare(strict_types=1);

namespace staabm\PHPStanDba;

/**
 * @api
 */
final class UnresolvableQueryDynamicFromException extends UnresolvableQueryException
{
    public static function getTip(): string
    {
        return 'Consider to simplify the FROM-clause construction.';
    }
}
