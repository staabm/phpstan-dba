<?php

namespace SyntaxErrorWithInferencePlaceolder;

use staabm\PHPStanDba\Tests\Fixture\Connection;

class Foo
{
    public function syntaxErrorSingleQuoted(Connection $connection, string $email)
    {
        $query = 'SELECT email, does_not_exist FROM ada WHERE email = :email AND '.$this->dynamicWhereSingleQuoted(rand(0, 100));
        $connection->preparedQuery($query, ['email' => $email]);
    }

    /**
     * simulating a dynamic where part, not relevant for the query overall result.
     *
     * @phpstandba-inference-placeholder '1=1'
     *
     * @return string
     */
    private function dynamicWhereSingleQuoted(int $i)
    {
        $where = ['1=1'];

        if ($i > 100) {
            $where[] = 'adaid = '.$i.'';
        }

        return implode(' AND ', $where);
    }

    public function syntaxErrorDoubleQuoted(Connection $connection, string $email)
    {
        $query = 'SELECT email, does_not_exist FROM ada WHERE email = :email AND '.$this->dynamicWhereDoubleQuoted(rand(0, 100));
        $connection->preparedQuery($query, ['email' => $email]);
    }

    /**
     * simulating a dynamic where part, not relevant for the query overall result.
     *
     * @phpstandba-inference-placeholder "1=1"
     *
     * @return string
     */
    private function dynamicWhereDoubleQuoted(int $i)
    {
        $where = ['1=1'];

        if ($i > 100) {
            $where[] = 'adaid = '.$i.'';
        }

        return implode(' AND ', $where);
    }

    public function syntaxErrorMixedQuoted(Connection $connection, string $email)
    {
        $query = 'SELECT email, does_not_exist FROM ada WHERE email = :email AND '.$this->dynamicWhereMixedQuoted(rand(0, 100));
        $connection->preparedQuery($query, ['email' => $email]);
    }

    /**
     * simulating a dynamic where part, not relevant for the query overall result.
     *
     * @phpstandba-inference-placeholder "email='test@example.com'"
     *
     * @return string
     */
    private function dynamicWhereMixedQuoted(int $i)
    {
        $where = ['1=1'];

        if ($i > 100) {
            $where[] = 'adaid = '.$i.'';
        }

        return implode(' AND ', $where);
    }
}
