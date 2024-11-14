<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use mysqli;
use mysqli_result;
use mysqli_sql_exception;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use staabm\PHPStanDba\Error;
use staabm\PHPStanDba\TypeMapping\MysqliTypeMapper;

final class MysqliQueryReflector implements QueryReflector, RecordingReflector
{
    private const MYSQL_SYNTAX_ERROR_CODE = 1064;

    private const MYSQL_UNKNOWN_COLUMN_IN_FIELDLIST = 1054;

    public const MYSQL_UNKNOWN_TABLE = 1146;

    private const MYSQL_INCORRECT_TABLE = 1103;

    /**
     * @api
     */
    public const NAME = 'mysqli';

    private const MYSQL_ERROR_CODES = [
        self::MYSQL_SYNTAX_ERROR_CODE,
        self::MYSQL_UNKNOWN_COLUMN_IN_FIELDLIST,
        self::MYSQL_UNKNOWN_TABLE,
        self::MYSQL_INCORRECT_TABLE,
    ];

    private const MAX_CACHE_SIZE = 50;

    /**
     * @var array<string, mysqli_sql_exception|array<object>|null>
     */
    private array $cache = [];

    private mysqli $db;

    private MysqliTypeMapper $typeMapper;

    public function __construct(mysqli $mysqli)
    {
        $this->db = $mysqli;
        // set a sane default.. atm this should not have any impact
        $this->db->set_charset('utf8');
        // enable exception throwing on php <8.1
        mysqli_report(\MYSQLI_REPORT_ERROR | \MYSQLI_REPORT_STRICT);
        $this->db->autocommit(false);
    }

    public function validateQueryString(string $queryString): ?Error
    {
        $result = $this->simulateQuery($queryString);
        if (! $result instanceof mysqli_sql_exception) {
            return null;
        }
        $e = $result;

        if (\in_array($e->getCode(), self::MYSQL_ERROR_CODES, true)) {
            if (
                self::MYSQL_SYNTAX_ERROR_CODE === $e->getCode()
                && QueryReflection::getRuntimeConfiguration()->isDebugEnabled()
            ) {
                return Error::forSyntaxError($e, $e->getCode(), $queryString);
            }

            return Error::forException($e, $e->getCode());
        }

        return null;
    }

    /**
     * @param self::FETCH_TYPE* $fetchType
     */
    public function getResultType(string $queryString, int $fetchType): ?Type
    {
        $result = $this->simulateQuery($queryString);
        if (! \is_array($result)) {
            return null;
        }

        $arrayBuilder = ConstantArrayTypeBuilder::createEmpty();

        $i = 0;
        foreach ($result as $val) {
            if (
                ! property_exists($val, 'name')
                || ! is_string($val->name)
                || ! property_exists($val, 'type')
                || ! is_int($val->type)
                || ! property_exists($val, 'flags')
                || ! is_int($val->flags)
                || ! property_exists($val, 'length')
                || ! is_int($val->length)
            ) {
                throw new ShouldNotHappenException();
            }

            if (self::FETCH_TYPE_ASSOC === $fetchType || self::FETCH_TYPE_BOTH === $fetchType) {
                $arrayBuilder->setOffsetValueType(
                    new ConstantStringType($val->name),
                    $this->typeMapper->mapToPHPStanType($val->type, $val->flags, $val->length)
                );
            }
            if (self::FETCH_TYPE_NUMERIC === $fetchType || self::FETCH_TYPE_BOTH === $fetchType) {
                $arrayBuilder->setOffsetValueType(
                    new ConstantIntegerType($i),
                    $this->typeMapper->mapToPHPStanType($val->type, $val->flags, $val->length)
                );
            }
            ++$i;
        }

        return $arrayBuilder->getArray();
    }

    public function setupDbaApi(?DbaApi $dbaApi): void
    {
        $this->typeMapper = new MysqliTypeMapper($dbaApi);
    }

    /**
     * @return mysqli_sql_exception|array<object>|null
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

        if (QueryReflection::getRuntimeConfiguration()->isAnalyzingWriteQueries()) {
            $this->db->begin_transaction();
        } else {
            $this->db->begin_transaction(\MYSQLI_TRANS_START_READ_ONLY);
        }

        try {
            $result = $this->db->query($simulatedQuery);

            if (! $result instanceof mysqli_result) {
                return $this->cache[$queryString] = null;
            }

            $resultInfo = $result->fetch_fields();
            $result->free();

            return $this->cache[$queryString] = $resultInfo;
        } catch (mysqli_sql_exception $e) {
            return $this->cache[$queryString] = $e;
        } finally {
            $this->db->rollback();
        }
    }

    public function getDatasource()
    {
        return $this->db;
    }
}
