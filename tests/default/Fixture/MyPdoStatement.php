<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Tests\Fixture;

class MyPdoStatement extends \PDOStatement
{
    public function execute(?array $params = null): bool
    {
        return parent::execute($params);
    }
}
