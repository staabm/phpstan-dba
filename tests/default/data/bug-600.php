<?php

namespace Bug600;

use Doctrine\DBAL\Connection;
use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
    public function uuidColumn(Connection $conn, string $id)
    {
        $row = $conn->fetchAssociative('SELECT name FROM uuid_entity WHERE id = :id', ['id' => $id]);
        assertType('array{name: string}|false', $row);
    }

    /**
     * @param non-empty-string $id
     */
    public function uuidColumnNonEmptyString(Connection $conn, string $id)
    {
        $row = $conn->fetchAssociative('SELECT name FROM uuid_entity WHERE id = ?', [$id]);
        assertType('array{name: string}|false', $row);
    }

    public function nullableUuidColumn(Connection $conn, ?string $parentId)
    {
        $rows = $conn->fetchAllAssociative('SELECT name FROM uuid_entity WHERE parent_id = :parentId', ['parentId' => $parentId]);
        assertType('list<array{name: string}>', $rows);
    }

    /**
     * @param list<string> $ids
     */
    public function uuidColumnInList(Connection $conn, array $ids)
    {
        $names = $conn->fetchFirstColumn('SELECT name FROM uuid_entity WHERE id IN (:ids)', ['ids' => $ids]);
        assertType('list<string>', $names);
    }

    public function uuidColumnPdo(PDO $pdo, string $id)
    {
        $stmt = $pdo->prepare('SELECT name FROM uuid_entity WHERE id = :id');
        $stmt->execute(['id' => $id]);
        foreach ($stmt as $row) {
            assertType('array{name: string, 0: string}', $row);
        }
    }

    public function jsonbColumn(Connection $conn, string $filter)
    {
        $row = $conn->fetchAssociative('SELECT pid FROM typemix WHERE c_jsonb @> :filter', ['filter' => $filter]);
        assertType('array{pid: int<1, 2147483647>}|false', $row);
    }

    public function enumColumn(Connection $conn, string $size)
    {
        $row = $conn->fetchAssociative('SELECT pid FROM typemix WHERE c_enum = :size', ['size' => $size]);
        assertType('array{pid: int<1, 2147483647>}|false', $row);
    }
}
