<?php

namespace Bug600;

use Doctrine\DBAL\Connection;

class UuidQueries
{
    public function select(Connection $conn, string $id)
    {
        $conn->fetchAssociative('SELECT name FROM uuid_entity WHERE id = :id', ['id' => $id]);
    }

    /**
     * @param list<string> $ids
     */
    public function selectInList(Connection $conn, array $ids)
    {
        $conn->fetchFirstColumn('SELECT name FROM uuid_entity WHERE id IN (:ids)', ['ids' => $ids]);
    }

    public function updateNullable(Connection $conn, string $id, ?string $parentId)
    {
        $conn->executeStatement('UPDATE uuid_entity SET parent_id = :parentId WHERE id = :id', ['id' => $id, 'parentId' => $parentId]);
    }

    public function insertIntoNotNullColumns(Connection $conn, string $id, string $name)
    {
        $conn->executeStatement('INSERT INTO uuid_entity (id, name) VALUES (:id, :name)', ['id' => $id, 'name' => $name]);
    }

    public function jsonb(Connection $conn, string $filter)
    {
        $conn->fetchAssociative('SELECT pid FROM typemix WHERE c_jsonb @> :filter', ['filter' => $filter]);
    }

    public function enum(Connection $conn, string $size)
    {
        $conn->fetchAssociative('SELECT pid FROM typemix WHERE c_enum = :size', ['size' => $size]);
    }
}
