<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use Dibi\Row;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;

final class DibiPropertyClassReflectionExtension implements PropertiesClassReflectionExtension
{
    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if (Row::class === $classReflection->getName()) {
            return true;
        }

        return false;
    }

    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        // how to get the type of property from classReflection?

        return new DibiRowPropertyReflection($classReflection, new IntegerType());
    }
}

final class DibiRowPropertyReflection implements PropertyReflection
{
    /**
     * @var ClassReflection
     */
    private $classReflection;

    /**
     * @var Type
     */
    private $type;

    public function __construct(ClassReflection $classReflection, Type $type)
    {
        $this->classReflection = $classReflection;
        $this->type = $type;
    }

    public function getDocComment(): ?string
    {
        return null;
    }

    public function getDeclaringClass(): ClassReflection
    {
        return $this->classReflection;
    }

    public function isStatic(): bool
    {
        return false;
    }

    public function isPrivate(): bool
    {
        return false;
    }

    public function isPublic(): bool
    {
        return true;
    }

    public function getReadableType(): Type
    {
        return $this->type;
    }

    public function getWritableType(): Type
    {
        return $this->type;
    }

    public function canChangeTypeAfterAssignment(): bool
    {
        return true;
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getDeprecatedDescription(): ?string
    {
        return null;
    }

    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
}
