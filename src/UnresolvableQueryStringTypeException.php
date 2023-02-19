<?php

declare(strict_types=1);

namespace staabm\PHPStanDba;

final class UnresolvableQueryStringTypeException extends UnresolvableQueryException
{
    public static function getTip(): string
    {
        return 'Consider replacing concatenated string-variables with prepared statements or @phpstandba-inference-placeholder.';
    }
}
