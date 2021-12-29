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

	public function getResultType(string $queryString):?Type {
		// XXX skip queries that are not SELECT

		$queryString .= ' LIMIT 0';
		$result = $this->db->query($queryString);
		if ($result) {
			$arrayBuilder = ConstantArrayTypeBuilder::createEmpty();

			/* Get field information for all result-columns */
			$finfo = $result->fetch_fields();

			foreach ($finfo as $val) {
				$arrayBuilder->setOffsetValueType(
					new ConstantStringType($val->name),
					$this->mapMysqlToPHPStanType($val->type, $val->flags)
				);

				/*
				printf("Name:      %s\n",   $val->name);
				printf("Table:     %s\n",   $val->table);
				printf("Max. Len:  %d\n",   $val->max_length);
				printf("Length:    %d\n",   $val->length);
				printf("charsetnr: %d\n",   $val->charsetnr);
				printf("Flags:     %s\n",   h_flags2txt($val->flags));
				printf("Type:      %s\n\n", h_type2txt($val->type));
				*/
			}
			$result->free();

			return new GenericObjectType(PDOStatement::class, [$arrayBuilder->getArray()]);
		}

		return null;
	}

	private function mapMysqlToPHPStanType(int $mysqlType, int $mysqlFlags): Type {
		foreach($this->flags2txt($mysqlFlags) as $flag) {
			switch($flag) {
				case 'NUM': {
					return new IntegerType();
				}
				// ???
				case 'NOT_NULL':
				case 'AUTO_INCREMENT':
				case 'PRI_KEY':
				case 'MULTIPLE_KEY':
				case 'NO_DEFAULT_VALUE':
			}
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
}
