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

    private const MAX_CACHE_SIZE = 50;

    /**
     * @var mysqli
     */
    private $db;

    /**
     * @var array<string, mysqli_sql_exception|mysqli_result|null>
     */
    private $cache;

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
        mysqli_report(\MYSQLI_REPORT_ERROR | \MYSQLI_REPORT_STRICT);

        $this->cache = [];
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
        $result = $this->simulateQuery($queryString);
        if (null === $result || $result instanceof mysqli_result) {
            return null;
        }

        if (\in_array($result->getCode(), [self::MYSQL_SYNTAX_ERROR_CODE, self::MYSQL_UNKNOWN_COLUMN_IN_FIELDLIST, self::MYSQL_UNKNOWN_TABLE], true)) {
            return new Error($result->getMessage(), $result->getCode());
        }

        return null;
    }

    /**
     * @param self::FETCH_TYPE* $fetchType
     */
    public function getResultType(string $queryString, int $fetchType): ?Type
    {
        $result = $this->simulateQuery($queryString);
        if (null === $result || $result instanceof mysqli_sql_exception) {
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

    /**
     * @return mysqli_sql_exception|mysqli_result|null
     */
    public function simulateQuery(string $queryString)
    {
        if (\count($this->cache) > self::MAX_CACHE_SIZE) {
            // make room for the next element
            array_shift($this->cache);
        }

        if (\array_key_exists($queryString, $this->cache)) {
            return $this->cache[$queryString];
        }

        $simulatedQuery = QuerySimulation::simulate($queryString);
        if (null === $simulatedQuery) {
            return $this->cache[$queryString] = null;
        }

        try {
            $result = $this->db->query($simulatedQuery);

            if (!$result instanceof mysqli_result) {
                return $this->cache[$queryString] = null;
            }

            return $this->cache[$queryString] = $result;
        } catch (mysqli_sql_exception $e) {
            return $this->cache[$queryString] = $e;
        }
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
