<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use function array_shift;
use Iterator;
use PDO;
use PDOException;
use PHPStan\ShouldNotHappenException;
use staabm\PHPStanDba\TypeMapping\PgsqlTypeMapper;
use staabm\PHPStanDba\TypeMapping\TypeMapper;

/**
 * @phpstan-type PDOColumnMeta array{name: string, table?: string, native_type: string, len: int, flags: list<string>}
 */
final class PdoPgSqlQueryReflector extends BasePdoQueryReflector
{
    /**
     * @api
     */
    public const NAME = 'pdo-pgsql';

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    /** @return PDOException|list<PDOColumnMeta>|null */
    protected function simulateQuery(string $queryString)
    {
        if (\array_key_exists($queryString, $this->cache)) {
            return $this->cache[$queryString];
        }

        if (\count($this->cache) > parent::MAX_CACHE_SIZE) {
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
            throw new \RuntimeException('Failed to start transaction', $e->getCode(), $e);
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
                throw new \RuntimeException('Failed to rollback transaction', $e->getCode(), $e);
            }
        }

        $this->cache[$queryString] = [];
        $columnCount = $stmt->columnCount();
        $columnIndex = 0;
        while ($columnIndex < $columnCount) {
            // see https://github.com/php/php-src/blob/master/ext/pdo_pgsql/pgsql_statement.c
            $columnMeta = $stmt->getColumnMeta($columnIndex);

            if (false === $columnMeta || !\array_key_exists('native_type', $columnMeta)) {
                throw new ShouldNotHappenException('Failed to get column meta for column index '.$columnIndex);
            }

            $table = $columnMeta['table'] ?? '';
            $columnMeta['flags'] = $this->emulateFlags(
                $columnMeta['native_type'],
                $table,
                $columnMeta['name']
            );

            if ($this->typeMapper->isNumericCol($columnMeta['native_type']) && 'count' === $columnMeta['name']) {
                $columnMeta['flags'][] = TypeMapper::FLAG_NOT_NULL;
            }

            // @phpstan-ignore-next-line
            $this->cache[$queryString][$columnIndex] = $columnMeta;
            ++$columnIndex;
        }

        return $this->cache[$queryString];
    }

    public function setupDbaApi(?DbaApi $dbaApi): void
    {
        $this->typeMapper = new PgsqlTypeMapper($dbaApi);
    }

    /**
     * @return Iterator<string, PgsqlTypeMapper::FLAG_*>
     */
    protected function checkInformationSchema(string $tableName): Iterator
    {
        if (null === $this->stmt) {
            $this->stmt = $this->pdo->prepare(
                <<<'PSQL'
                SELECT column_name, column_default, is_nullable
                FROM information_schema.columns
                WHERE table_name = ?
                PSQL
            );
        }

        $this->stmt->execute([$tableName]);
        $result = $this->stmt->fetchAll(PDO::FETCH_ASSOC);

        /** @var array{column_default?: string, column_name: string, is_nullable: string} $row */
        foreach ($result as $row) {
            $default = $row['column_default'] ?? '';
            $columnName = $row['column_name'];
            $isNullable = 'YES' === $row['is_nullable'];

            if (!$isNullable) {
                yield $columnName => PgsqlTypeMapper::FLAG_NOT_NULL;
            }
            if (str_contains($default, 'nextval')) {
                yield $columnName => PgsqlTypeMapper::FLAG_AUTO_INCREMENT;
            }
        }
    }
}
