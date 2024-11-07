<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SchemaReflection;

/**
 * @api
 */
final class Table
{
    private string $name;

    /**
     * @var list<Column>
     */
    private array $columns = [];

    /**
     * @param list<Column> $columns
     */
    public function __construct(string $name, array $columns)
    {
        $this->name = $name;
        $this->columns = $columns;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return list<Column>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }
}
