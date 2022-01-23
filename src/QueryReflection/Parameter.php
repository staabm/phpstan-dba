<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\Type\Type;

final class Parameter
{
    /**
     * @var non-empty-string|null
     */
    public $name;
    /**
     * @var Type
     */
    public $type;
    /**
     * @var ?string
     */
    public $simulatedValue;
    /**
     * @var bool
     */
    public $isOptional;

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

        if (!str_starts_with($name, ':')) {
            $name = ':'.$name;
        }

        $this->name = $name;
    }
}
