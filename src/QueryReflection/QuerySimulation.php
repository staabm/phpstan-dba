<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use mysqli;
use mysqli_result;
use mysqli_sql_exception;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use staabm\PHPStanDba\Error;
use staabm\PHPStanDba\Types\MysqlIntegerRanges;

/**
 * @internal
 */
final class QuerySimulation
{
	static public function simulate(string $queryString): ?string {
		$queryString = self::stripTraillingLimit($queryString);

		if (null === $queryString) {
			return null;
		}
		$queryString .= ' LIMIT 0';

		return $queryString;
	}

	static private function stripTraillingLimit(string $queryString): ?string
	{
		return preg_replace('/\s*LIMIT\s+\d+\s*(,\s*\d*)?$/i', '', $queryString);
	}
}
