<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SchemaReflection;

use PHPStan\Type\Type;

final class SchemaReflection
{
    /**
     * @var array<string, Table|null>
     */
    private array $tables = [];

    /**
     * @var callable(string):?\PHPStan\Type\Type
     */
    private $queryResolver;

    /**
     * @param callable(string):?\PHPStan\Type\Type $queryResolver
     */
    public function __construct(callable $queryResolver)
    {
        $this->queryResolver = $queryResolver;
    }

    public function getTable(string $tableName): ?Table
    {
        if (\array_key_exists($tableName, $this->tables)) {
            return $this->tables[$tableName];
        }

        $resultType = ($this->queryResolver)('SELECT * FROM ' . $tableName);
        if (! $resultType instanceof Type) {
            return $this->tables[$tableName] = null;
        }
        $arrays = $resultType->getConstantArrays();
        if (count($arrays) !== 1) {
            return $this->tables[$tableName] = null;
        }

        $resultType = $arrays[0];
        $keyTypes = $resultType->getKeyTypes();
        $valueTypes = $resultType->getValueTypes();
        $columns = [];
        foreach ($keyTypes as $i => $keyType) {
            foreach ($keyType->getConstantStrings() as $constantString) {
                $columns[] = new Column($constantString->getValue(), $valueTypes[$i]);
            }
        }

        return $this->tables[$tableName] = new Table($tableName, $columns);
    }
}
