<?php

declare(strict_types=1);

namespace staabm\PHPStanDba;

final class UnresolvableQueryMixedTypeException extends UnresolvableQueryException
{
    public static function getTip(): string
    {
        return 'Make sure all variables involved have a non-mixed type and array-types are specified.';
    }
}
