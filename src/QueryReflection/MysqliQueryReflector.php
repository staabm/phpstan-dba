<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use mysqli;
use mysqli_result;
use mysqli_sql_exception;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
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
     * @var array<string, mysqli_sql_exception|list<object>|null>
     */
    private $cache = [];

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

        $this->nativeTypes = [];
        $this->nativeFlags = [];

        $constants = get_defined_constants(true);
        foreach ($constants['mysqli'] as $c => $n) {
            if (!\is_int($n)) {
                throw new ShouldNotHappenException();
            }
            if (preg_match('/^MYSQLI_TYPE_(.*)/', $c, $m)) {
                if (!\is_string($m[1])) {
                    throw new ShouldNotHappenException();
                }
                $this->nativeTypes[$n] = $m[1];
            } elseif (preg_match('/MYSQLI_(.*)_FLAG$/', $c, $m)) {
                if (!\is_string($m[1])) {
                    throw new ShouldNotHappenException();
                }
                if (!\array_key_exists($n, $this->nativeFlags)) {
                    $this->nativeFlags[$n] = $m[1];
                }
            }
        }
    }

    public function validateQueryString(string $queryString): ?Error
    {
        $result = $this->simulateQuery($queryString);
        if (!$result instanceof mysqli_sql_exception) {
            return null;
        }
        $e = $result;

        if (\in_array($e->getCode(), [self::MYSQL_SYNTAX_ERROR_CODE, self::MYSQL_UNKNOWN_COLUMN_IN_FIELDLIST, self::MYSQL_UNKNOWN_TABLE], true)) {
            $message = $e->getMessage();

            // make error string consistent across mysql/mariadb
            $message = str_replace(' MySQL server', ' MySQL/MariaDB server', $message);
            $message = str_replace(' MariaDB server', ' MySQL/MariaDB server', $message);

            // to ease debugging, print the error we simulated
            if (self::MYSQL_SYNTAX_ERROR_CODE === $e->getCode() && QueryReflection::getRuntimeConfiguration()->isDebugEnabled()) {
                $simulatedQuery = QuerySimulation::simulate($queryString);
                $message = $message."\n\nSimulated query: ".$simulatedQuery;
            }

            return new Error($message, $e->getCode());
        }

        return null;
    }

    /**
     * @param self::FETCH_TYPE* $fetchType
     */
    public function getResultType(string $queryString, int $fetchType): ?Type
    {
        $result = $this->simulateQuery($queryString);
        if (!\is_array($result)) {
            return null;
        }

        $arrayBuilder = ConstantArrayTypeBuilder::createEmpty();

        $i = 0;
        foreach ($result as $val) {
            if (!property_exists($val, 'name') || !property_exists($val, 'type') || !property_exists($val, 'flags') || !property_exists($val, 'length')) {
                throw new ShouldNotHappenException();
            }

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

        return $arrayBuilder->getArray();
    }

    /**
     * @return mysqli_sql_exception|list<object>|null
     */
    private function simulateQuery(string $queryString)
    {
        if (\array_key_exists($queryString, $this->cache)) {
            return $this->cache[$queryString];
        }

        if (\count($this->cache) > self::MAX_CACHE_SIZE) {
            // make room for the next element by randomly removing a existing one
            array_shift($this->cache);
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

            $resultInfo = $result->fetch_fields();
            $result->free();

            return $this->cache[$queryString] = $resultInfo;
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
                case 'PART_KEY':
                case 'MULTIPLE_KEY':
                case 'NO_DEFAULT_VALUE':
            }
        }

        $phpstanType = null;
        $mysqlIntegerRanges = new MysqlIntegerRanges();

        if ($numeric) {
            if ($unsigned) {
                if (3 === $length) { // bool aka tinyint(1)
                    $phpstanType = $mysqlIntegerRanges->unsignedTinyInt();
                }
                if (4 === $length) {
                    $phpstanType = $mysqlIntegerRanges->unsignedTinyInt();
                }
                if (5 === $length) {
                    $phpstanType = $mysqlIntegerRanges->unsignedSmallInt();
                }
                if (8 === $length) {
                    $phpstanType = $mysqlIntegerRanges->unsignedMediumInt();
                }
                if (10 === $length) {
                    $phpstanType = $mysqlIntegerRanges->unsignedInt();
                }
                if (20 === $length) {
                    $phpstanType = $mysqlIntegerRanges->unsignedBigInt();
                }
            } else {
                if (1 == $length) {
                    $phpstanType = $mysqlIntegerRanges->signedTinyInt();
                }
                if (4 === $length) {
                    $phpstanType = $mysqlIntegerRanges->signedTinyInt();
                }
                if (6 === $length) {
                    $phpstanType = $mysqlIntegerRanges->signedSmallInt();
                }
                if (9 === $length) {
                    $phpstanType = $mysqlIntegerRanges->signedMediumInt();
                }
                if (11 === $length) {
                    $phpstanType = $mysqlIntegerRanges->signedInt();
                }
                if (20 === $length) {
                    $phpstanType = $mysqlIntegerRanges->signedBigInt();
                }
                if (22 === $length) {
                    $phpstanType = $mysqlIntegerRanges->signedBigInt();
                }
            }
        }

        if ($autoIncrement) {
            $phpstanType = $mysqlIntegerRanges->unsignedInt();
        }

        if (null === $phpstanType) {
            switch ($this->type2txt($mysqlType)) {
                case 'DOUBLE':
                case 'NEWDECIMAL':
                    $phpstanType = new FloatType();
                    break;
                case 'LONGLONG':
                case 'LONG':
                case 'SHORT':
                case 'YEAR':
                case 'BIT':
                case 'INT24':
                    $phpstanType = new IntegerType();
                    break;
                case 'BLOB':
                case 'CHAR':
                case 'STRING':
                case 'VAR_STRING':
                case 'JSON':
                case 'DATE':
                case 'TIME':
                case 'DATETIME':
                case 'TIMESTAMP':
                    $phpstanType = new StringType();
                    break;
                default:
                    $phpstanType = new MixedType();
            }
        }

        if (QueryReflection::getRuntimeConfiguration()->isStringifyTypes()) {
            $numberType = new UnionType([new IntegerType(), new FloatType()]);
            $isNumber = $numberType->isSuperTypeOf($phpstanType)->yes();

            if ($isNumber) {
                $phpstanType = new IntersectionType([
                    new StringType(),
                    new AccessoryNumericStringType(),
                ]);
            }
        }

        if (false === $notNull) {
            $phpstanType = TypeCombinator::addNull($phpstanType);
        }

        return $phpstanType;
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
