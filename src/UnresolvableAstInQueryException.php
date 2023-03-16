<?php

declare(strict_types=1);

namespace staabm\PHPStanDba;

final class UnresolvableAstInQueryException extends UnresolvableQueryException
{
    public static function getTip(): string
    {
        return 'The query cannot be analysed via AST yet, consider creating a pull request or an issue on github';
    }
}
