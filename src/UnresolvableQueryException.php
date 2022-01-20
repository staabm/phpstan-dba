<?php

namespace staabm\PHPStanDba;

final class UnresolvableQueryException extends DbaException
{
    public const RULE_TIP = 'Make sure all variables involved have a non-mixed type and array-types are specified.';

    public function asRuleMessage(): string
    {
        return 'Unresolvable Query: '.$this->getMessage().'.';
    }
}
