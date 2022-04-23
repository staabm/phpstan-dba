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
use function strtoupper;

/**
 * @phpstan-type ColumnMeta array{name: string, table: string, native_type: string, len: int, flags: array<int, string>, precision: int<0, max>, pdo_type: PDO::PARAM_* }
 */
final class PdoQueryReflector extends BasePdoQueryReflector implements QueryReflector
{
    public function __construct(PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        parent::__construct($pdo, new MysqlTypeMapper());
    }

    /** @return PDOException|list<ColumnMeta>|null */
    protected function simulateQuery(string $queryString)
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
            $this->pdo->beginTransaction();
        } catch (PDOException $e) {
            // not all drivers may support transactions
        }

        try {
            $stmt = $this->pdo->query($simulatedQuery);
        } catch (PDOException $e) {
            return $this->cache[$queryString] = $e;
        } finally {
            try {
                $this->pdo->rollBack();
            } catch (PDOException $e) {
                // not all drivers may support transactions
            }
        }

        $this->cache[$queryString] = [];
        $columnCount = $stmt->columnCount();
        $columnIndex = 0;
        while ($columnIndex < $columnCount) {
            // see https://github.com/php/php-src/blob/master/ext/pdo_mysql/mysql_statement.c
            /** @var ColumnMeta|false */
            $columnMeta = $stmt->getColumnMeta($columnIndex);

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

            $flags = $this->emulateFlags($columnMeta['native_type'], $columnMeta['table'], $columnMeta['name']);
            foreach ($flags as $flag) {
                $columnMeta['flags'][] = $flag;
            }

            // @phpstan-ignore-next-line
            $this->cache[$queryString][$columnIndex] = $columnMeta;
            ++$columnIndex;
        }

        return $this->cache[$queryString];
    }

    /**
     * @return Iterator<string, MysqlTypeMapper::FLAG_*>
     */
    protected function checkInformationSchema(string $tableName): Iterator
    {
        if (null === $this->stmt) {
            $this->stmt = $this->pdo->prepare(
                // EXTRA, COLUMN_NAME seems to be nullable in mariadb
                'SELECT
                    coalesce(COLUMN_NAME, "") as COLUMN_NAME,
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
}
