<?php

namespace Test;

use Doctrine\DBAL\Connection;

class Test
{
    private Connection $connection;

    public function getIds(iterable $ids): array
    {
        return $this
            ->connection
            ->executeQuery(
                'SELECT id FROM table WHERE id IN (:list)',
                ['list' => $ids],
                ['list' => DBAL\Connection::PARAM_INT_ARRAY],
            )
            ->fetchFirstColumn();
    }
}
