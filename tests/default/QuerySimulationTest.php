<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPUnit\Framework\TestCase;
use staabm\PHPStanDba\QueryReflection\QuerySimulation;

class QuerySimulationTest extends TestCase
{
    public function testIntersectionTypeInt()
    {
        // non-empty-array<int, int>
        $builder = ConstantArrayTypeBuilder::createEmpty();
        $builder->setOffsetValueType(new IntegerType(), new IntegerType());

        $simulatedValue = QuerySimulation::simulateParamValueType($builder->getArray(), false);
        $this->assertNotNull($simulatedValue);
    }

    public function testIntersectionTypeString()
    {
        // non-empty-array<string, int>
        $builder = ConstantArrayTypeBuilder::createEmpty();
        $builder->setOffsetValueType(new StringType(), new IntegerType());

        $simulatedValue = QuerySimulation::simulateParamValueType($builder->getArray(), false);
        $this->assertNotNull($simulatedValue);
    }

    public function testIntersectionTypeMix()
    {
        // non-empty-array<string, int>
        $builder = ConstantArrayTypeBuilder::createEmpty();
        $builder->setOffsetValueType(new StringType(), new IntegerType());
        $builder->setOffsetValueType(new IntegerType(), new FloatType());

        $simulatedValue = QuerySimulation::simulateParamValueType($builder->getArray(), false);
        $this->assertNotNull($simulatedValue);
    }
}
