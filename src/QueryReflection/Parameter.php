<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\Type\Type;

/**
 * @api
 */
final class Parameter
{
    /**
     * @var non-empty-string|null
     */
    public ?string $name = null;

    public Type $type;

    public ?string $simulatedValue = null;

    public bool $isOptional;

    /**
     * @param non-empty-string|null $name
     */
    public function __construct(?string $name, Type $type, ?string $simulatedValue, bool $isOptional)
    {
        $this->type = $type;
        $this->simulatedValue = $simulatedValue;
        $this->isOptional = $isOptional;

        if (null === $name) {
            return;
        }

        if (! str_starts_with($name, ':')) {
            $name = ':' . $name;
        }

        $this->name = $name;
    }
}
