<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\PdoReflection;

use PDOStatement;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use staabm\PHPStanDba\QueryReflection\QueryReflector;

class PdoStatementObjectType extends GenericObjectType
{
    private Type $bothType;

    /**
     * @param QueryReflector::FETCH_TYPE* $fetchType
     */
    public function __construct(Type $bothType, int $fetchType)
    {
        $this->bothType = $bothType;

        $rowTypeInFetchMode = $this->reduceBothType($bothType, $fetchType);

        parent::__construct(PDOStatement::class, [$rowTypeInFetchMode]);
    }

    public function getRowType(): Type
    {
        $genericTypes = $this->getTypes();

        return $genericTypes[0];
    }

    /**
     * @param QueryReflector::FETCH_TYPE* $fetchType
     */
    public function newWithFetchType(int $fetchType): self
    {
        return new self($this->bothType, $fetchType);
    }

    /**
     * @param QueryReflector::FETCH_TYPE* $fetchType
     */
    private function reduceBothType(Type $bothType, int $fetchType): Type
    {
        if (!$bothType instanceof ConstantArrayType) {
            return $bothType;
        }

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
                if (QueryReflector::FETCH_TYPE_NUMERIC === $fetchType && $keyType instanceof ConstantIntegerType) {
                    $builder->setOffsetValueType($keyType, $valueTypes[$i]);
                } elseif (QueryReflector::FETCH_TYPE_ASSOC === $fetchType && $keyType instanceof ConstantStringType) {
                    $builder->setOffsetValueType($keyType, $valueTypes[$i]);
                }
            }

            return $builder->getArray();
        }

        if (QueryReflector::FETCH_TYPE_CLASS === $fetchType) {
            return new ArrayType(new IntegerType(), new ObjectType('stdClass'));
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
                return new GenericObjectType(PDOStatement::class, [new ObjectType('stdClass')]);
            case QueryReflector::FETCH_TYPE_KEY_VALUE:
                $arrayBuilder = ConstantArrayTypeBuilder::createEmpty();
                $arrayBuilder->setOffsetValueType(new ConstantIntegerType(0), new MixedType());
                $arrayBuilder->setOffsetValueType(new ConstantIntegerType(1), new MixedType());

                return new GenericObjectType(PDOStatement::class, [$arrayBuilder->getArray()]);
            case QueryReflector::FETCH_TYPE_NUMERIC:
                return new GenericObjectType(PDOStatement::class, [new ArrayType(IntegerRangeType::fromInterval(0, null), $pdoScalar)]);
            case QueryReflector::FETCH_TYPE_ASSOC:
                return new GenericObjectType(PDOStatement::class, [new ArrayType(new StringType(), $pdoScalar)]);
            case QueryReflector::FETCH_TYPE_BOTH:
                return new GenericObjectType(PDOStatement::class, [new ArrayType($arrayKey, $pdoScalar)]);
            case QueryReflector::FETCH_TYPE_COLUMN:
                return new GenericObjectType(PDOStatement::class, [$pdoScalar]);
        }

        return new GenericObjectType(PDOStatement::class, [new MixedType()]);
    }
}
