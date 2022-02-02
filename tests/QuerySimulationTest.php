<?php

namespace staabm\PHPStanDba\Tests;

use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\IntegerType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\TestCase;
use staabm\PHPStanDba\QueryReflection\QuerySimulation;

class QuerySimulationTest extends TestCase
{
    public function testIntersectionType()
    {
        $builder = ConstantArrayTypeBuilder::createEmpty();
        $builder->setOffsetValueType(new IntegerType(), new IntegerType());

        $simulatedValue = QuerySimulation::simulateParamValueType($builder->getArray(), false);
        $this->assertNotNull($simulatedValue);
    }
}
