<?php

declare(strict_types=1);

namespace staabm\PHPStanDba;

/**
 * @api
 */
final class UnresolvableQueryInvalidAfterSimulationException extends UnresolvableQueryException
{
    public static function getTip(): string
    {
        return 'Consider to simplify the query string construction.';
    }
}
