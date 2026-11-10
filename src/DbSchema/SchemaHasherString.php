<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\DbSchema;

final class SchemaHasherString implements SchemaHasher
{
    private string $hash;

    public function __construct(string $hash)
    {
        $this->hash = $hash;
    }

    public function hashDb(): string
    {
        return $this->hash;
    }
}
