<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\PdoReflection;

use PDOStatement;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
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

    public function getRowType():Type {
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
}
