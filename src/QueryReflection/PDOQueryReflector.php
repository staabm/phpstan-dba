<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PDO;
use PDOException;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use staabm\PHPStanDba\Error;
use staabm\PHPStanDba\TypeMapping\PostgresTypeMapper;
use staabm\PHPStanDba\TypeMapping\TypeMapper;

final class PDOQueryReflector implements QueryReflector
{
    private const PSQL_INVALID_TEXT_REPRESENTATION = '22P02';
    private const PSQL_UNDEFINED_COLUMN = '42703';
    private const PSQL_UNDEFINED_TABLE = '42P01';

    private const PDO_ERROR_CODES = [
        self::PSQL_INVALID_TEXT_REPRESENTATION,
        self::PSQL_UNDEFINED_COLUMN,
        self::PSQL_UNDEFINED_TABLE,
    ];

    private const MAX_CACHE_SIZE = 50;

    /**
     * @var array<string, PDOException|array|false>
     */
    private array $cache = [];

    private TypeMapper $typeMapper;

    public function __construct(private PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->typeMapper = new PostgresTypeMapper();
    }

    public function validateQueryString(string $queryString): ?Error
    {
        $result = $this->simulateQuery($queryString);

        if (!$result instanceof PDOException) {
            return null;
        }

        $e = $result;
        if (\in_array($e->getCode(), self::SUPPORTED_PDO_CODES, true)) {
            $message = $e->getMessage();

            if (
                self::PSQL_INVALID_TEXT_REPRESENTATION === $e->getCode()
                && QueryReflection::getRuntimeConfiguration()->isDebugEnabled()
            ) {
                $simulatedQuery = QuerySimulation::simulate($queryString);
                $message = $message."\n\nSimulated query: '".$simulatedQuery."' Failed.";
            }

            return new Error($message, $e->getCode());
        }

        return null;
    }

    public function getResultType(string $queryString, int $fetchType): ?Type
    {
        $result = $this->simulateQuery($queryString);

        if (!\is_array($result)) {
            return null;
        }

        $arrayBuilder = ConstantArrayTypeBuilder::createEmpty();

        $i = 0;
        foreach ($result as $val) {
            if (
                !property_exists($val, 'name')
                || !property_exists($val, 'type')
                || !property_exists($val, 'flags')
                || !property_exists($val, 'length')
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

    /** @return PDOException|array<int|string, mixed>|false|null */
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
            $result = $this->pdo->query($simulatedQuery);
        } catch (PDOException $e) {
            return $this->cache[$queryString] = $e;
        }

        if (false === $result) {
            return $this->cache[$queryString] = false;
        }

        $this->cache[$queryString] = $result->fetchAll();

        return $this->cache[$queryString];
    }
}
