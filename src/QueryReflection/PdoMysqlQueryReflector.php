<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use Iterator;
use PDO;
use PDOException;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Type;
use staabm\PHPStanDba\TypeMapping\MysqlTypeMapper;
use staabm\PHPStanDba\TypeMapping\TypeMapper;

/**
 * @phpstan-import-type ColumnMeta from BasePdoQueryReflector
 *
 * @final
 */
class PdoMysqlQueryReflector extends BasePdoQueryReflector
{
    /**
     * @api
     */
    public const NAME = 'pdo-mysql';

    public function __construct(PDO $pdo)
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        parent::__construct($pdo);
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

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->query($simulatedQuery);
        } catch (PDOException $e) {
            return $this->cache[$queryString] = $e;
        } finally {
            $this->pdo->rollBack();
        }

        $this->cache[$queryString] = [];
        $columnCount = $stmt->columnCount();
        $columnIndex = 0;
        while ($columnIndex < $columnCount) {
            $columnMeta = $stmt->getColumnMeta($columnIndex);

            if (false === $columnMeta || !\array_key_exists('table', $columnMeta)) {
                throw new ShouldNotHappenException('Failed to get column meta for column index '.$columnIndex);
            }

            //  Native type may not be set, for example in case of JSON column.
            if (!\array_key_exists('native_type', $columnMeta)) {
                $columnMeta['native_type'] = \PDO::PARAM_INT === $columnMeta['pdo_type'] ? 'INT' : 'STRING';
            }

            $flags = $this->emulateFlags($columnMeta['native_type'], $columnMeta['table'], $columnMeta['name']);
            foreach ($flags as $flag) {
                $columnMeta['flags'][] = $flag;
            }

            $this->cache[$queryString][$columnIndex] = $columnMeta;
            ++$columnIndex;
        }

        return $this->cache[$queryString];
    }

    public function setupDbaApi(?DbaApi $dbaApi): void
    {
        $this->typeMapper = new MysqlTypeMapper($dbaApi);
    }

    /**
     * @return Iterator<string, TypeMapper::FLAG_*>
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
                yield $columnName => TypeMapper::FLAG_AUTO_INCREMENT;
            }
            if (str_contains($columnType, 'unsigned')) {
                yield $columnName => TypeMapper::FLAG_UNSIGNED;
            }
        }
    }
}
