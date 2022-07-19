<?php

namespace SyntaxErrorInDynamicQuery;

use staabm\PHPStanDba\Tests\Fixture\Connection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;

class Foo
{
    public function syntaxError(Connection $connection, string $email)
    {
        $query = 'SELECT email,, adaid FROM ada WHERE email = :email AND '.$this->dynamicWhere(rand(0, 100));
        $connection->preparedQuery($query, ['email' => $email]);
    }

    /**
     * simulating a dynamic where part, not relevant for the query overall result.
     *
     * @phpstandba-inference-placeholder '1=1'
     *
     * @return string
     */
    private function dynamicWhere(int $i)
    {
        $where = ['1=1'];

        if ($i > 100) {
            $where[] = 'adaid = '.$i.'';
        }

        return implode(' AND ', $where);
    }
}
