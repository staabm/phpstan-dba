<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use Iterator;
use PDO;
use PDOException;
use PDOStatement;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use staabm\PHPStanDba\Error;
use staabm\PHPStanDba\TypeMapping\MysqlTypeMapper;

/**
 * @phpstan-type ColumnMeta array{name: string, native_type: string, len: int, flags: list<string>}
 */
final class PdoQueryReflector implements QueryReflector
{
    private const PSQL_INVALID_TEXT_REPRESENTATION = '22P02';
    private const PSQL_UNDEFINED_COLUMN = '42703';
    private const PSQL_UNDEFINED_TABLE = '42P01';

    private const MYSQL_SYNTAX_ERROR_CODE = '42000';
    private const MYSQL_UNKNOWN_COLUMN_IN_FIELDLIST = '42S22';
    private const MYSQL_UNKNOWN_TABLE = '42S02';

    private const PDO_SYNTAX_ERROR_CODES = [
        self::MYSQL_SYNTAX_ERROR_CODE,
        self::PSQL_INVALID_TEXT_REPRESENTATION,
    ];

    private const PDO_ERROR_CODES = [
        self::PSQL_INVALID_TEXT_REPRESENTATION,
        self::PSQL_UNDEFINED_COLUMN,
        self::PSQL_UNDEFINED_TABLE,
        self::MYSQL_SYNTAX_ERROR_CODE,
        self::MYSQL_UNKNOWN_COLUMN_IN_FIELDLIST,
        self::MYSQL_UNKNOWN_TABLE,
    ];

    private const MAX_CACHE_SIZE = 50;

    /**
     * @var array<string, PDOException|list<ColumnMeta>|null>
     */
    private array $cache = [];

    private MysqlTypeMapper $typeMapper;

    // @phpstan-ignore-next-line
    private ?PDOStatement $stmt = null;
    /**
     * @var array<string, array<string, list<string>>>
     */
    private array $emulatedFlags = [];

    public function __construct(private PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->typeMapper = new MysqlTypeMapper();
    }

    public function validateQueryString(string $queryString): ?Error
    {
        $result = $this->simulateQuery($queryString);

        if (!$result instanceof PDOException) {
            return null;
        }

        $e = $result;
        if (\in_array($e->getCode(), self::PDO_ERROR_CODES, true)) {
            $message = $e->getMessage();

            // make error string consistent across mysql/mariadb
            $message = str_replace(' MySQL server', ' MySQL/MariaDB server', $message);
            $message = str_replace(' MariaDB server', ' MySQL/MariaDB server', $message);

            // make error string consistent across pdo/mysqli driver
            $message = str_replace('SQLSTATE['.$e->getCode().']: ', '', $message);

            // to ease debugging, print the error we simulated
            if (
                \in_array($e->getCode(), self::PDO_SYNTAX_ERROR_CODES, true)
                && QueryReflection::getRuntimeConfiguration()->isDebugEnabled()
            ) {
                $simulatedQuery = QuerySimulation::simulate($queryString);
                $message = $message."\n\nSimulated query: '".$simulatedQuery."' Failed.";
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
            if (self::FETCH_TYPE_ASSOC === $fetchType || self::FETCH_TYPE_BOTH === $fetchType) {
                $arrayBuilder->setOffsetValueType(
                    new ConstantStringType($val['name']),
                    $this->typeMapper->mapToPHPStanType($val['native_type'], $val['flags'], $val['len'])
                );
            }
            if (self::FETCH_TYPE_NUMERIC === $fetchType || self::FETCH_TYPE_BOTH === $fetchType) {
                $arrayBuilder->setOffsetValueType(
                    new ConstantIntegerType($i),
                    $this->typeMapper->mapToPHPStanType($val['native_type'], $val['flags'], $val['len'])
                );
            }
            ++$i;
        }

        return $arrayBuilder->getArray();
    }

    /** @return PDOException|list<ColumnMeta>|null */
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

        $this->cache[$queryString] = [];
        $columnCount = $result->columnCount();
        $columnIndex = 0;
        while ($columnIndex < $columnCount) {
            // see https://github.com/php/php-src/blob/master/ext/pdo_mysql/mysql_statement.c
            $columnMeta = $result->getColumnMeta($columnIndex);

            if (false === $columnMeta) {
                throw new ShouldNotHappenException('Failed to get column meta for column index '.$columnIndex);
            }

            if (
                !\array_key_exists('name', $columnMeta)
                || !\array_key_exists('table', $columnMeta)
                || !\array_key_exists('native_type', $columnMeta)
                || !\array_key_exists('flags', $columnMeta)
                || !\array_key_exists('len', $columnMeta)
            ) {
                throw new ShouldNotHappenException();
            }

            $flags = $this->emulateMysqlFlags($columnMeta['native_type'], $columnMeta['table'], $columnMeta['name']);
            foreach ($flags as $flag) {
                $columnMeta['flags'][] = $flag;
            }

            // @phpstan-ignore-next-line
            $this->cache[$queryString][$columnIndex] = $columnMeta;
            ++$columnIndex;
        }

        // @phpstan-ignore-next-line
        return $this->cache[$queryString];
    }

    /**
     * @return list<string>
     */
    private function emulateMysqlFlags(string $mysqlType, string $tableName, string $columnName): array
    {
        if (\array_key_exists($tableName, $this->emulatedFlags)) {
            $emulatedFlags = [];
            if (\array_key_exists($columnName, $this->emulatedFlags[$tableName])) {
                $emulatedFlags = $this->emulatedFlags[$tableName][$columnName];
            }

            if ($this->isNumericCol($mysqlType)) {
                $emulatedFlags[] = MysqlTypeMapper::FLAG_NUMERIC;
            }

            return $emulatedFlags;
        }

        $this->emulatedFlags[$tableName] = [];

        // determine flags of all columns of the given table once
        $schemaFlags = $this->checkInformationSchema($tableName);
        foreach ($schemaFlags as $schemaColumnName => $flag) {
            if (!\array_key_exists($schemaColumnName, $this->emulatedFlags[$tableName])) {
                $this->emulatedFlags[$tableName][$schemaColumnName] = [];
            }
            $this->emulatedFlags[$tableName][$schemaColumnName][] = $flag;
        }

        return $this->emulateMysqlFlags($mysqlType, $tableName, $columnName);
    }

    /**
     * @return Iterator<string, MysqlTypeMapper::FLAG_*>
     */
    private function checkInformationSchema(string $tableName): Iterator
    {
        if (null === $this->stmt) {
            $this->stmt = $this->pdo->prepare(
                // EXTRA,COLUMN_NAME seems to be nullable in mariadb
                'SELECT
                        coalesce(COLUMN_NAME, "") as COLUMN_NAME
                        coalesce(EXTRA, "") as EXTRA,
                        COLUMN_TYPE
                      FROM information_schema.columns
                      WHERE table_name = ? AND table_schema = DATABASE()'
            );
        }
        $this->stmt->execute([$tableName]);
        $result = $this->stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($result as $row) {
            $extra = $row['EXTRA'];
            $columnType = $row['COLUMN_TYPE'];
            $columnName = $row['COLUMN_NAME'];

            if (str_contains($extra, 'auto_increment')) {
                yield $columnName => MysqlTypeMapper::FLAG_AUTO_INCREMENT;
            }
            if (str_contains($columnType, 'unsigned')) {
                yield $columnName => MysqlTypeMapper::FLAG_UNSIGNED;
            }
        }
    }

    private function isNumericCol(string $mysqlType): bool
    {
        return match (strtoupper($mysqlType)) {
            'LONGLONG',
                'LONG',
                'SHORT',
                'TINY',
                'YEAR',
                'BIT',
                'INT24' => true,
                default => false,
        };
    }
}
