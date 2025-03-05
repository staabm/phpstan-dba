<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\PdoReflection;

use PDOStatement;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectShapeType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use staabm\PHPStanDba\QueryReflection\QueryReflector;

class PdoStatementObjectType extends ObjectType
{
    private ?Type $bothType = null;

    /**
     * @var null|QueryReflector::FETCH_TYPE*
     */
    private ?int $fetchType = null;

    public function getRowType(): Type
    {
        if ($this->bothType === null || $this->fetchType === null) {
            throw new ShouldNotHappenException();
        }
        return $this->reduceBothType($this->bothType, $this->fetchType);
    }

    public function getIterableValueType(): Type
    {
        return $this->getRowType();
    }

    /**
     * @param QueryReflector::FETCH_TYPE* $fetchType
     */
    public static function newWithBothAndFetchType(Type $bothType, int $fetchType): self
    {
        $new = new self(PDOStatement::class);
        $new->bothType = $bothType;
        $new->fetchType = $fetchType;
        return $new;
    }

    /**
     * @param QueryReflector::FETCH_TYPE* $fetchType
     */
    public function newWithFetchType(int $fetchType): self
    {
        $new = new self($this->getClassName(), $this->getSubtractedType());
        $new->bothType = $this->bothType;
        $new->fetchType = $fetchType;
        return $new;
    }

    /**
     * @param QueryReflector::FETCH_TYPE* $fetchType
     */
    private function reduceBothType(Type $bothType, int $fetchType): Type
    {
        $arrays = $bothType->getConstantArrays();
        if (count($arrays) !== 1) {
            return $bothType;
        }
        $bothType = $arrays[0];

        if (\count($bothType->getValueTypes()) <= 0) {
            return $bothType;
        }

        // turn a BOTH typed statement into either NUMERIC or ASSOC
        if (
            QueryReflector::FETCH_TYPE_NUMERIC === $fetchType || QueryReflector::FETCH_TYPE_ASSOC === $fetchType
        ) {
            $builder = ConstantArrayTypeBuilder::createEmpty();

            $keyTypes = $bothType->getKeyTypes();
            $valueTypes = $bothType->getValueTypes();

            foreach ($keyTypes as $i => $keyType) {
                if (QueryReflector::FETCH_TYPE_NUMERIC === $fetchType && $keyType->isInteger()->yes()) {
                    $builder->setOffsetValueType($keyType, $valueTypes[$i]);
                } elseif (QueryReflector::FETCH_TYPE_ASSOC === $fetchType && $keyType->isString()->yes()) {
                    $builder->setOffsetValueType($keyType, $valueTypes[$i]);
                }
            }

            return $builder->getArray();
        }

        if (QueryReflector::FETCH_TYPE_CLASS === $fetchType) {
            $keyTypes = $bothType->getKeyTypes();
            $valueTypes = $bothType->getValueTypes();

            $properties = [];
            foreach ($keyTypes as $i => $keyType) {
                if (! $keyType->isString()->yes()) {
                    continue;
                }
                $properties[(string) $keyType->getValue()] = $valueTypes[$i];
            }

            return new IntersectionType([
                new ObjectType(\stdClass::class),
                new ObjectShapeType($properties, []),
            ]);
        }

        // both types contains numeric and string keys, therefore the count is doubled
        if (QueryReflector::FETCH_TYPE_KEY_VALUE === $fetchType && \count($bothType->getValueTypes()) >= 4) {
            $valueTypes = $bothType->getValueTypes();

            return new ArrayType($valueTypes[0], $valueTypes[2]);
        }

        // not yet supported fetch type - or $fetchType == BOTH
        return $bothType;
    }

    /**
     * @param QueryReflector::FETCH_TYPE* $fetchType
     */
    public static function createDefaultType(int $fetchType): Type
    {
        // we assume native PDO is not able to return bool.
        $pdoScalar = new UnionType([new IntegerType(), new FloatType(), new StringType(), new NullType()]);
        $arrayKey = new BenevolentUnionType([new IntegerType(), new StringType()]);

        switch ($fetchType) {
            case QueryReflector::FETCH_TYPE_CLASS:
                return self::newWithBothAndFetchType(new ObjectType('stdClass'), $fetchType);
            case QueryReflector::FETCH_TYPE_KEY_VALUE:
                $arrayBuilder = ConstantArrayTypeBuilder::createEmpty();
                $arrayBuilder->setOffsetValueType(new ConstantIntegerType(0), new MixedType());
                $arrayBuilder->setOffsetValueType(new ConstantIntegerType(1), new MixedType());

                return self::newWithBothAndFetchType($arrayBuilder->getArray(), $fetchType);
            case QueryReflector::FETCH_TYPE_NUMERIC:
                return self::newWithBothAndFetchType(new ArrayType(IntegerRangeType::fromInterval(0, null), $pdoScalar), $fetchType);
            case QueryReflector::FETCH_TYPE_ASSOC:
                return self::newWithBothAndFetchType(new ArrayType(new StringType(), $pdoScalar), $fetchType);
            case QueryReflector::FETCH_TYPE_BOTH:
                return self::newWithBothAndFetchType(new ArrayType($arrayKey, $pdoScalar), $fetchType);
            case QueryReflector::FETCH_TYPE_COLUMN:
                return self::newWithBothAndFetchType($pdoScalar, $fetchType);
        }

        return self::newWithBothAndFetchType(new MixedType(), $fetchType);
    }

    // differentiate objects based on the local properties,
    // to make sure TypeCombinator::union() will not normalize separate objects away.
    // this means we need to implement equals() and isSuperTypeOf().
    public function equals(Type $type): bool
    {
        if ($type instanceof self) {
            return $type->fetchType !== null
                && $type->bothType !== null
                && $this->bothType !== null
                && $type->fetchType === $this->fetchType
                && $type->bothType->equals(
                    $this->bothType
                );
        }

        return parent::equals($type);
    }

    public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
    {
        if ($type instanceof self) {
            return IsSuperTypeOfResult::createFromBoolean(
                $type->fetchType !== null
                && $type->bothType !== null
                && $this->bothType !== null
                && $type->fetchType === $this->fetchType
                && $type->bothType->equals($this->bothType)
            );
        }

        return parent::isSuperTypeOf($type);
    }
}
