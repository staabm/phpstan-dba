<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use Dibi\Connection;
use Dibi\ConstraintViolationException;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use staabm\PHPStanDba\Error;
use staabm\PHPStanDba\TypeMapping\MysqliTypeMapper;

final class DibiQueryReflector implements QueryReflector, RecordingReflector
{
    public const NAME = 'dibi-mysql';

    /** @var array<string, \Throwable|list<object>|null> */
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
        $this->typeMapper = new MysqliTypeMapper();
    }

    public function validateQueryString(string $queryString): ?Error
    {
        $result = $this->simulateQuery($queryString);

        if ($result instanceof \Throwable) {
            return new Error($result->getMessage(), $result->getCode());
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

        foreach ($result as $val) {
            $type = new MixedType();

            if (is_numeric($val->getVendorInfo('type')) && is_numeric($val->getVendorInfo('flags')) && is_numeric($val->getVendorInfo('length'))) {
                $type = $this->typeMapper->mapToPHPStanType((int) $val->getVendorInfo('type'), (int) $val->getVendorInfo('flags'), (int) $val->getVendorInfo('length'));
            }

            $arrayBuilder->setOffsetValueType(new ConstantStringType($val->getName()), $type);
        }

        return $arrayBuilder->getArray();
    }

    /**
     * @return \Throwable|list<object>|null
     */
    private function simulateQuery(string $queryString)
    {
        $originalQueryString = $queryString;

        if (\array_key_exists($originalQueryString, $this->cache)) {
            return $this->cache[$originalQueryString];
        }

        $queryString = str_replace('%in', '(1)', $queryString);
        $queryString = str_replace('%lmt', 'LIMIT 1', $queryString);
        $queryString = str_replace('%ofs', ', 1', $queryString);
        $queryString = preg_replace('#%(i|s)#', '"1"', $queryString) ?? '';
        $queryString = preg_replace('#%(t|d)#', '"2000-1-1"', $queryString) ?? '';
        $queryString = preg_replace('#%(and|or)#', '(1 = 1)', $queryString) ?? '';
        $queryString = preg_replace('#%~?like~?#', '"%1%"', $queryString) ?? '';

        if (strpos($queryString, '%n') > 0) {
            $queryString = null;
        } elseif (strpos($queryString, '%ex') > 0) {
            $queryString = null;
        } elseif (0 !== preg_match('#^\s*(START|ROLLBACK|SET|SAVEPOINT|SHOW)#i', $queryString)) {
            $queryString = null;
        }

        if (null === $queryString) {
            return $this->cache[$originalQueryString] = null;
        }

        $this->connection->query('START transaction');

        try {
            $result = $this->connection->query($queryString);
            $resultInfo = $result->getColumns();

            return $this->cache[$originalQueryString] = $resultInfo;
        } catch (ConstraintViolationException $e) {
            return $this->cache[$originalQueryString] = null;
        } catch (\Dibi\DriverException|\Dibi\Exception $e) {
            return $this->cache[$originalQueryString] = $e;
        } finally {
            $this->connection->query('ROLLBACK');
        }
    }

    public function getDatasource()
    {
        return $this->connection;
    }
}
