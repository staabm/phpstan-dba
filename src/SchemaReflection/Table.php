<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SchemaReflection;

/**
 * @api
 */
final class Table
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var list<Column>
     */
    private $columns = [];

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
