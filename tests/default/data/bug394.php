<?php

namespace Bug394;

class X {
    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    public function bug394(mixed $conditionId)
    {
        if ($conditionId !== null) {
            $query = 'SELECT email, adaid FROM ada WHERE adaid = ?';
            $fetchResult = $this->conn->fetchAssociative($query, [$conditionId]);
            assertType('array{email: string, adaid: int<-32768, 32767>}|false', $fetchResult);
        }
    }
}
