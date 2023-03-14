<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SchemaReflection;

use SqlFtw\Sql\Expression\RootNode;

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
    private $joinType;

    /**
     * @var Table
     */
    private $table;

    /**
     * @var RootNode
     */
    private $joinCondition;

    /**
     * @param self::TYPE_* $joinType
     */
    public function __construct(string $joinType, Table $table, RootNode $joinCondition)
    {
        $this->joinType = $joinType;
        $this->table = $table;
        $this->joinCondition = $joinCondition;
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

    public function getJoinCondition(): RootNode
    {
        return $this->joinCondition;
    }
}
