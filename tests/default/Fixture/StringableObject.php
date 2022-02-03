<?php

namespace staabm\PHPStanDba\Tests\Fixture;

class StringableObject
{
    public function __toString()
    {
        return 'foo';
    }
}
