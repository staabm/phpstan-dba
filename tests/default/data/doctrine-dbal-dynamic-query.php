<?php

namespace DoctrineDbalDynamicQueryTest;

use Doctrine\DBAL\Connection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;
use function PHPStan\Testing\assertType;

class Foo {
    public function fetchOneWithDynamicQueryPart(Connection $conn, string $email)
    {
        $query = 'SELECT email, adaid FROM ada WHERE email = :email AND '.$this->dynamicWhere(rand(0,100));
        $fetchResult = $conn->fetchOne($query, ['email' => $email]);
        assertType('string|false', $fetchResult);
    }

    /**
     * simulating a dynamic where part, not relevant for the query overall result
     *
     * @return QueryReflector::UNRELEVANT_QUERY_PART
     */
    private function dynamicWhere(int $i) {
        $where = ['1=1'];

        if ($i > 100) {
            $where[] = 'adaid = '.$i.'';
        }

        // @phpstan-ignore-next-line
        return implode(' AND ', $where);
    }
}
