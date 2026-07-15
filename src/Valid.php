<?php

declare(strict_types=1);

namespace staabm\PHPStanDba;

final class Valid
{
    public static function __set_state(array $array): self
    {
        return new self();
    }
}
