<?php

namespace InferencePlaceholderTest;

use Doctrine\DBAL\Connection;
use function PHPStan\Testing\assertType;

class Foo
{
    public function fetchOneWithDynamicQueryPart(Connection $conn, string $email)
    {
        $query = 'SELECT email, adaid FROM ada WHERE email = :email AND '.$this->dynamicWhere(rand(0, 100));
        $fetchResult = $conn->fetchOne($query, ['email' => $email]);
        assertType('string|false', $fetchResult);

        $query = 'SELECT email, adaid FROM ada WHERE email = :email AND '.self::dynamicWhere(rand(0, 100));
        $fetchResult = $conn->fetchOne($query, ['email' => $email]);
        assertType('string|false', $fetchResult);
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

    /**
     * simulating a dynamic where part, not relevant for the query overall result.
     *
     * @phpstandba-inference-placeholder '1=1'
     *
     * @return string
     */
    static private function staticDynamicWhere(int $i)
    {
        $where = ['1=1'];

        if ($i > 100) {
            $where[] = 'adaid = '.$i.'';
        }

        return implode(' AND ', $where);
    }
}
