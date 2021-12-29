<?php declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PDOStatement;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class QueryReflection {
	/**
	 * @var \mysqli
	 */
	private $db;
	/**
	 * @var array<int, string>
	 */
	private $nativeTypes;
	/**
	 * @var array<int, string>
	 */
	private $nativeFlags;

	public function __construct() {
		$this->db = new \mysqli('mysql57.ab', 'testuser', 'test', 'logitel_clxmobilenet');

		if ($this->db->connect_errno) {
			throw new \Exception(sprintf("Connect failed: %s\n", $this->db->connect_error));
		}

		// set a sane default.. atm this should not have any impact
		$this->db->set_charset('utf8');

		$this->nativeTypes = array();
		$this->nativeFlags = array();

		$constants = get_defined_constants(true);
		foreach ($constants['mysqli'] as $c => $n) {
			if (preg_match('/^MYSQLI_TYPE_(.*)/', $c, $m)) {
				$this->nativeTypes[$n] = $m[1];
			} elseif (preg_match('/MYSQLI_(.*)_FLAG$/', $c, $m)) {
				if (!array_key_exists($n, $this->nativeFlags)) {
					$this->nativeFlags[$n] = $m[1];
				}
			}
		}
	}

	public function getResultType(Expr $expr, Scope $scope):?Type {
		$queryString = $this->resolveQueryString($expr, $scope);

		if ($this->getQueryType($queryString) !== 'SELECT') {
			return null;
		}

		$queryString .= ' LIMIT 0';
		$result = $this->db->query($queryString);
		if ($result) {
			$arrayBuilder = ConstantArrayTypeBuilder::createEmpty();

			/* Get field information for all result-columns */
			$finfo = $result->fetch_fields();

			foreach ($finfo as $val) {
				$arrayBuilder->setOffsetValueType(
					new ConstantStringType($val->name),
					$this->mapMysqlToPHPStanType($val->type, $val->flags, $val->length)
				);
			}
			$result->free();

			return new GenericObjectType(PDOStatement::class, [$arrayBuilder->getArray()]);
		}

		return null;
	}

	private function resolveQueryString(Expr $expr, Scope $scope):string {
		if ($expr instanceof Concat) {
			$left = $expr->left;
			$right = $expr->right;

			$leftString = $this->resolveQueryString($left, $scope);
			$rightString = $this->resolveQueryString($right, $scope);

			if ($leftString && $rightString) {
				return $leftString . $rightString;
			}
			if ($leftString) {
				return $leftString;
			}
			if ($rightString) {
				return $rightString;
			}
		}

		$type = $scope->getType($expr);
		if ($type instanceof ConstantStringType) {
			return $type->getValue();
		}

		$integerType = new IntegerType();
		if ($integerType->isSuperTypeOf($type)->yes()) {
			return "1";
		}

		$stringType = new StringType();
		if ($stringType->isSuperTypeOf($type)->yes()) {
			return "1=1";
		}

		$floatType = new FloatType();
		if ($floatType->isSuperTypeOf($type)->yes()) {
			return "1.0";
		}

		throw new \Exception(sprintf('Unexpected expression type %s', get_class($type)));
	}

	private function mapMysqlToPHPStanType(int $mysqlType, int $mysqlFlags, int $length): Type {
		$numeric = false;
		$notNull = false;
		$unsigned = false;
		$autoIncrement = false;

		foreach($this->flags2txt($mysqlFlags) as $flag) {
			switch($flag) {
				case 'NUM': {
					$numeric = true;
					break;
				}
				case 'NOT_NULL': {
					$notNull = true;
					break;
				}
				case 'AUTO_INCREMENT': {
					$autoIncrement = true;
					break;
				}
				case 'UNSIGNED':
				{
					$unsigned = true;
					break;
				}

				// ???
				case 'PRI_KEY':
				case 'MULTIPLE_KEY':
				case 'NO_DEFAULT_VALUE':
			}
		}

		$mysqlIntegerRanges = new MysqlIntegerRanges();
		$phpstanType = null;
		if ($numeric) {
			if ($length == 1) {
				$phpstanType = $mysqlIntegerRanges->signedTinyInt();
			}
			if ($length == 11) {
				$phpstanType = $mysqlIntegerRanges->signedInt();
			}
		}

		if ($autoIncrement) {
			$phpstanType = $mysqlIntegerRanges->unsignedInt();
		}

		if ($phpstanType) {
			if ($notNull === false) {
				$phpstanType = TypeCombinator::addNull($phpstanType);
			}

			return $phpstanType;
		}

		switch($this->type2txt($mysqlType)) {
			case 'LONGLONG':
			case 'LONG':
			case 'SHORT':
				return new IntegerType();
			case 'CHAR':
			case 'STRING':
			case 'VAR_STRING':
				return new StringType();
			case 'DATE': // ???
			case 'DATETIME': // ???
		}

		return new MixedType();
	}

	private function type2txt(int $typeId): ?string
	{
		return array_key_exists($typeId, $this->nativeTypes)? $this->nativeTypes[$typeId] : null;
	}

	/**
	 * @return list<string>
	 */
	private function flags2txt(int $flagId): array
	{
		$result = array();
		foreach ($this->nativeFlags as $n => $t) {
			if ($flagId & $n) $result[] = $t;
		}
		return $result;
	}

	private function getQueryType(string $query): ?string
	{
		$query = ltrim($query);

		if (preg_match('/^\s*\(?\s*(SELECT|SHOW|UPDATE|INSERT|DELETE|REPLACE|CREATE|CALL|OPTIMIZE)/i', $query, $matches)) {
			return strtoupper($matches[1]);
		}

		return null;
	}
}
