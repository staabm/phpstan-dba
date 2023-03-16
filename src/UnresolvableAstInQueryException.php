<?php

declare(strict_types=1);

namespace staabm\PHPStanDba;

final class UnresolvableAstInQueryException extends UnresolvableQueryException
{
    public static function getTip(): string
    {
        return 'The query cannot be analysed via AST yet, please create a issue on https://github.com/staabm/phpstan-dba/issues with reproducing example code.';
    }
}
