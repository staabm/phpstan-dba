<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\DbSchema;

interface SchemaHasher
{
    public function hashDb(): string;
}
