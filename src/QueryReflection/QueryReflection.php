<?php declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PDOStatement;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;

final class QueryReflection {
	public function __construct() {
	}

	public function getResultType(string $queryString):?Type {
		if ($queryString === 'SELECT name, id FROM foo') {
			// XXX build dynamically
			$arrayBuilder = ConstantArrayTypeBuilder::createEmpty();
			$arrayBuilder->setOffsetValueType(new ConstantStringType('id'), IntegerRangeType::fromInterval(0, null));
			$arrayBuilder->setOffsetValueType(new ConstantStringType('name'), new StringType());

			return TypeCombinator::union(
				new GenericObjectType(PDOStatement::class, [$arrayBuilder->getArray()]),
				new ConstantBooleanType(false)
			);
		}

		return null;
	}
}
