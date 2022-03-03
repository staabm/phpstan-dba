<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PDO;
use PDOException;
use PDOStatement;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use staabm\PHPStanDba\Error;
use staabm\PHPStanDba\TypeMapping\PgsqlTypeMapper;
use staabm\PHPStanDba\TypeMapping\TypeMapper;

/**
 * @phpstan-type ColumnMeta array{name: string, table: string, native_type: string, len: int, flags: list<string>}
 */
abstract class BasePdoQueryReflector
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

    protected const MAX_CACHE_SIZE = 50;

    /**
     * @var array<string, PDOException|list<ColumnMeta>|null>
     */
    protected array $cache = [];

    protected TypeMapper $typeMapper;

    // @phpstan-ignore-next-line
    protected ?PDOStatement $stmt = null;
    /**
     * @var array<string, array<string, list<string>>>
     */
    protected array $emulatedFlags = [];

    public function __construct(protected PDO $pdo, TypeMapper $typeMapper)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->typeMapper = $typeMapper;
    }

    public function validateQueryString(string $queryString): ?Error
    {
        $result = $this->simulateQuery($queryString);

        if (!$result instanceof PDOException) {
            return null;
        }

        $e = $result;
        if (\in_array($e->getCode(), self::PDO_ERROR_CODES, true)) {
            if (
                \in_array($e->getCode(), self::PDO_SYNTAX_ERROR_CODES, true)
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

        if (!\is_array($result)) {
            return null;
        }

        $arrayBuilder = ConstantArrayTypeBuilder::createEmpty();

        $i = 0;
        foreach ($result as $val) {
            if (QueryReflector::FETCH_TYPE_ASSOC === $fetchType || QueryReflector::FETCH_TYPE_BOTH === $fetchType) {
                $arrayBuilder->setOffsetValueType(
                    new ConstantStringType($val['name']),
                    $this->typeMapper->mapToPHPStanType($val['native_type'], $val['flags'], $val['len'])
                );
            }
            if (QueryReflector::FETCH_TYPE_NUMERIC === $fetchType || QueryReflector::FETCH_TYPE_BOTH === $fetchType) {
                $arrayBuilder->setOffsetValueType(
                    new ConstantIntegerType($i),
                    $this->typeMapper->mapToPHPStanType($val['native_type'], $val['flags'], $val['len'])
                );
            }
            ++$i;
        }

        return $arrayBuilder->getArray();
    }

    /**
     * @return list<string>
     */
    protected function emulateFlags(string $nativeType, string $tableName, string $columnName): array
    {
        if (\array_key_exists($tableName, $this->emulatedFlags)) {
            $emulatedFlags = [];
            if (\array_key_exists($columnName, $this->emulatedFlags[$tableName])) {
                $emulatedFlags = $this->emulatedFlags[$tableName][$columnName];
            }

            if ($this->typeMapper->isNumericCol($nativeType)) {
                $emulatedFlags[] = PgsqlTypeMapper::FLAG_NUMERIC;
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

        return $this->emulateFlags($nativeType, $tableName, $columnName);
    }

    abstract protected function simulateQuery(string $queryString);

    abstract protected function checkInformationSchema(string $tableName);
}
