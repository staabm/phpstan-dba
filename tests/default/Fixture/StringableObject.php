<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Tests\Fixture;

class StringableObject
{
    public function __toString()
    {
        return 'foo';
    }
}
