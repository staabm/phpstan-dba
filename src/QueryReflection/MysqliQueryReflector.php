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

final class MysqliQueryReflector implements QueryReflector
{
    public const MYSQL_SYNTAX_ERROR_CODE = 1064;
    public const MYSQL_UNKNOWN_COLUMN_IN_FIELDLIST = 1054;
    public const MYSQL_UNKNOWN_TABLE = 1146;

    public const MYSQL_HOST_NOT_FOUND = 2002;

    /**
     * @var mysqli
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

    public function __construct(mysqli $mysqli)
    {
        $this->db = $mysqli;
        // set a sane default.. atm this should not have any impact
        $this->db->set_charset('utf8');
		// enable exception throwing on php <8.1
		$this->db->report_mode = MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT;

        $this->nativeTypes = [];
        $this->nativeFlags = [];

        $constants = get_defined_constants(true);
        foreach ($constants['mysqli'] as $c => $n) {
            if (preg_match('/^MYSQLI_TYPE_(.*)/', $c, $m)) {
                $this->nativeTypes[$n] = $m[1];
            } elseif (preg_match('/MYSQLI_(.*)_FLAG$/', $c, $m)) {
                if (!\array_key_exists($n, $this->nativeFlags)) {
                    $this->nativeFlags[$n] = $m[1];
                }
            }
        }
    }

    public function validateQueryString(string $queryString): ?Error
    {
        try {
            $simulatedQuery = QuerySimulation::simulate($queryString);
            if (null === $simulatedQuery) {
                return null;
            }

            $this->db->query($simulatedQuery);

            return null;
        } catch (mysqli_sql_exception $e) {
            if (\in_array($e->getCode(), [self::MYSQL_SYNTAX_ERROR_CODE, self::MYSQL_UNKNOWN_COLUMN_IN_FIELDLIST, self::MYSQL_UNKNOWN_TABLE], true)) {
                return new Error($e->getMessage(), $e->getCode());
            }

            return null;
        }
    }

    /**
     * @param self::FETCH_TYPE* $fetchType
     */
    public function getResultType(string $queryString, int $fetchType): ?Type
    {
        try {
            $simulatedQuery = QuerySimulation::simulate($queryString);
            if (null === $simulatedQuery) {
                return null;
            }
            $result = $this->db->query($simulatedQuery);

            if (!$result instanceof mysqli_result) {
                return null;
            }
        } catch (mysqli_sql_exception $e) {
            return null;
        }

        $arrayBuilder = ConstantArrayTypeBuilder::createEmpty();

        /* Get field information for all result-columns */
        $finfo = $result->fetch_fields();

        $i = 0;
        foreach ($finfo as $val) {
            if (self::FETCH_TYPE_ASSOC === $fetchType || self::FETCH_TYPE_BOTH === $fetchType) {
                $arrayBuilder->setOffsetValueType(
                    new ConstantStringType($val->name),
                    $this->mapMysqlToPHPStanType($val->type, $val->flags, $val->length)
                );
            }
            if (self::FETCH_TYPE_NUMERIC === $fetchType || self::FETCH_TYPE_BOTH === $fetchType) {
                $arrayBuilder->setOffsetValueType(
                    new ConstantIntegerType($i),
                    $this->mapMysqlToPHPStanType($val->type, $val->flags, $val->length)
                );
            }
            ++$i;
        }
        $result->free();

        return $arrayBuilder->getArray();
    }

    private function mapMysqlToPHPStanType(int $mysqlType, int $mysqlFlags, int $length): Type
    {
        $numeric = false;
        $notNull = false;
        $unsigned = false;
        $autoIncrement = false;

        foreach ($this->flags2txt($mysqlFlags) as $flag) {
            switch ($flag) {
                case 'NUM':
                    $numeric = true;
                    break;

                case 'NOT_NULL':
                    $notNull = true;
                    break;

                case 'AUTO_INCREMENT':
                    $autoIncrement = true;
                    break;

                case 'UNSIGNED':
                    $unsigned = true;
                    break;

                // ???
                case 'PRI_KEY':
                case 'MULTIPLE_KEY':
                case 'NO_DEFAULT_VALUE':
            }
        }

        $mysqlIntegerRanges = new MysqlIntegerRanges();
        $phpstanType = null;
        if ($numeric) {
            if (1 == $length) {
                $phpstanType = $mysqlIntegerRanges->signedTinyInt();
            }
            if (11 == $length) {
                $phpstanType = $mysqlIntegerRanges->signedInt();
            }
        }

        if ($autoIncrement) {
            $phpstanType = $mysqlIntegerRanges->unsignedInt();
        }

        if ($phpstanType) {
            if (false === $notNull) {
                $phpstanType = TypeCombinator::addNull($phpstanType);
            }

            return $phpstanType;
        }

        switch ($this->type2txt($mysqlType)) {
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
        return \array_key_exists($typeId, $this->nativeTypes) ? $this->nativeTypes[$typeId] : null;
    }

    /**
     * @return list<string>
     */
    private function flags2txt(int $flagId): array
    {
        $result = [];
        foreach ($this->nativeFlags as $n => $t) {
            if ($flagId & $n) {
                $result[] = $t;
            }
        }

        return $result;
    }
}
