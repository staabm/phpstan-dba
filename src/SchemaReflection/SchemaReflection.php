<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SchemaReflection;

use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;

final class SchemaReflection
{
    /**
     * @var array<string, Table|null>
     */
    private $tables = [];

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

        $resultType = ($this->queryResolver)('SELECT * FROM '.$tableName);
        if (!$resultType instanceof ConstantArrayType) {
            return $this->tables[$tableName] = null;
        }

        $keyTypes = $resultType->getKeyTypes();
        $valueTypes = $resultType->getValueTypes();
        $columns = [];
        foreach ($keyTypes as $i => $keyType) {
            if (!$keyType instanceof ConstantStringType) {
                throw new ShouldNotHappenException();
            }

            $columns[] = new Column($keyType->getValue(), $valueTypes[$i]);
        }

        return $this->tables[$tableName] = new Table($tableName, $columns);
    }
}
