<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SchemaReflection;

use PHPStan\Type\Type;

/**
 * @api
 */
final class Column
{
    private string $name;

    private Type $type;

    public function __construct(string $name, Type $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
