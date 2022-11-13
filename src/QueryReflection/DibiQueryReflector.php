<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use Dibi\Connection;
use Dibi\DriverException;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use staabm\PHPStanDba\Error;
use staabm\PHPStanDba\TypeMapping\MysqliTypeMapper;

final class DibiQueryReflector implements QueryReflector, RecordingReflector
{
    private const MYSQL_SYNTAX_ERROR_CODE = 1064;
    private const MYSQL_UNKNOWN_COLUMN_IN_FIELDLIST = 1054;
    public const MYSQL_UNKNOWN_TABLE = 1146;
    public const MYSQL_INCORRECT_TABLE = 1103;

    public const MYSQL_HOST_NOT_FOUND = 2002;

    public const NAME = 'mysqli';

    private const MYSQL_ERROR_CODES = [
        self::MYSQL_SYNTAX_ERROR_CODE,
        self::MYSQL_UNKNOWN_COLUMN_IN_FIELDLIST,
        self::MYSQL_UNKNOWN_TABLE,
        self::MYSQL_INCORRECT_TABLE,
    ];

    private const MAX_CACHE_SIZE = 50;

    /** @var array<string, DriverException|list<object>|null> */
    private $cache = [];

    /** @var Connection */
    private $connection;

    /**
     * @var MysqliTypeMapper
     */
    private $typeMapper;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        // set a sane default.. atm this should not have any impact

        $this->typeMapper = new MysqliTypeMapper();
    }

    public function validateQueryString(string $queryString): ?Error
    {
        $result = $this->simulateQuery($queryString);
        if (!$result instanceof DriverException) {
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
        /** @var array<\Dibi\Reflection\Column> $result */
        $result = $this->simulateQuery($queryString);

        if (!\is_array($result)) {
            return null;
        }

        $arrayBuilder = ConstantArrayTypeBuilder::createEmpty();

        $i = 0;
        foreach ($result as $val) {
            $arrayBuilder->setOffsetValueType(
                new ConstantStringType($val->getName()),
                $this->typeMapper->mapToPHPStanType($val->getVendorInfo('type'), $val->getVendorInfo('flags'), $val->getVendorInfo('length'))
            );
            $arrayBuilder->setOffsetValueType(
                new ConstantIntegerType($i),
                $this->typeMapper->mapToPHPStanType($val->getVendorInfo('type'), $val->getVendorInfo('flags'), $val->getVendorInfo('length'))
            );
            ++$i;
        }


        return $arrayBuilder->getArray();
    }

    /**
     * @return DriverException|list<object>|null
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

        $this->connection->query('START transaction');

        try {
            $result = $this->connection->query($simulatedQuery);
            $resultInfo = $result->getColumns();

            return $this->cache[$queryString] = $resultInfo;
        } catch (DriverException $e) {
            return $this->cache[$queryString] = $e;
        } finally {
            $this->connection->query('ROLLBACK');
        }
    }

    public function getDatasource(): Connection
    {
        return $this->connection;
    }
}
