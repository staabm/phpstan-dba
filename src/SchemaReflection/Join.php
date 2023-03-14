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
     * @var string
     */
    private $joinType;
    /**
     * @var Table
     */
    private $table;

    /**
     * @param self::TYPE_* $joinType
     */
    public function __construct(string $joinType, Table $table) {
        $this->joinType = $joinType;
        $this->table = $table;
    }

    public function getTable(): Table
    {
        return $this->table;
    }

    /**
     * @return self::TYPE_*
     */
    public function getJoinType(): string
    {
        return $this->joinType;
    }
}
