<?php

namespace staabm\PHPStanDba;

final class UnresolvableQueryStringTypeException extends UnresolvableQueryException
{
    public static function getTip(): string
    {
        return 'Consider replacing variable strings with prepared statements or @phpstandba-inference-placeholder.';
    }
}
