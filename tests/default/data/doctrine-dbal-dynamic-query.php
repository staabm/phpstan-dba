<?php

namespace DoctrineDbalDynamicQueryTest;

use Doctrine\DBAL\Connection;
use function PHPStan\Testing\assertType;

class Foo {
    public function fetchOneWithDynamicQueryPart(Connection $conn)
    {
        $query = 'SELECT email, adaid FROM ada WHERE email != "" AND '.$this->dynamicWhere(rand(0,100));
        $fetchResult = $conn->fetchOne($query, []);
        assertType('string|false', $fetchResult);
    }

    /**
     * simulating a dynamic where part, not relevant for the query overall result
     *
     * @return '1=1'
     */
    private function dynamicWhere(int $i) {
        $where = ['1=1'];

        if ($i > 100) {
            $where[] = 'adaid = '.$i.'';
        }

        return implode(' AND ', $where);
    }
}
