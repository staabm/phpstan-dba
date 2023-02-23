<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SchemaReflection;

/**
 * @api
 */
final class Join
{
    public const TYPE_INNER = 'inner';

    public const TYPE_OUTER = 'outer';

    /**
     * @var self::TYPE_*
     */
    private $type;

    /**
     * @var Table
     */
    private $table;

    /**
     * @param self::TYPE_* $type
     */
    public function __construct(string $type, Table $table)
    {
        $this->type = $type;
        $this->table = $table;
    }

    /**
     * @return self::TYPE_*
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function getTable(): Table
    {
        return $this->table;
    }
}
