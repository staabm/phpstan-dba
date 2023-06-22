<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\DbSchema;

class LazySchemaHasher implements SchemaHasher
{
    /**
     * @var callable():SchemaHasher
     */
    private $schemaHasherFactory;

    /**
     * @var SchemaHasher|null
     */
    private $schemaHasher;

    /**
     * @param callable():SchemaHasher $schemaHasherFactory
     */
    public function __construct(callable $schemaHasherFactory)
    {
        $this->schemaHasherFactory = $schemaHasherFactory;
    }

    public function hashDb(): string
    {
        if (null === $this->schemaHasher) {
            $this->schemaHasher = ($this->schemaHasherFactory)();
        }

        return $this->schemaHasher->hashDb();
    }
}
