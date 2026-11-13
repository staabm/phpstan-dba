<?php

namespace MysqliTest;

use mysqli;
use function PHPStan\Testing\assertType;

class AdaClass1 {
}

class AdaClass2 {
}


class Foo
{
    public function ooQuerySelected(mysqli $mysqli)
    {
        $result = $mysqli->query('SELECT email, adaid FROM ada');

        $field = 'email';
        if (rand(0, 1)) {
            $field = 'adaid';
        }

        foreach ($result as $row) {
            assertType('int<-32768, 32767>', $row['adaid']);
            assertType('string', $row['email']);
            assertType('int<-32768, 32767>|string', $row[$field]);
        }
    }

    public function ooQuery(mysqli $mysqli, string $query)
    {
        $result = $mysqli->query($query);
        assertType('mysqli_result|true', $result);
    }

    public function fnQuerySelected(mysqli $mysqli)
    {
        $result = mysqli_query($mysqli, 'SELECT email, adaid FROM ada');

        foreach ($result as $row) {
            assertType('int<-32768, 32767>', $row['adaid']);
            assertType('string', $row['email']);
        }
    }

    public function fnQuery(mysqli $mysqli, string $query)
    {
        $result = mysqli_query($mysqli, $query);
        assertType('mysqli_result|true', $result);
    }

    public function fnFetchTypes(mysqli $mysqli)
    {
        $result = mysqli_query($mysqli, 'SELECT email, adaid FROM ada');

        while ($row = mysqli_fetch_assoc($result)) {
            assertType('array{email: string, adaid: int<-32768, 32767>}', $row);
        }

        while ($row = mysqli_fetch_row($result)) {
            assertType('array{string, int<-32768, 32767>}', $row);
        }

        while ($row = mysqli_fetch_object($result)) {
            assertType('int<-32768, 32767>', $row->adaid);
            assertType('string', $row->email);
        }

        while ($row = mysqli_fetch_array($result)) {
            assertType('array{0: string, 1: int<-32768, 32767>, email: string, adaid: int<-32768, 32767>}', $row);
        }
    }
  
    public function unionResult(mysqli $mysqli)
    {
        $queries = ['SELECT adaid FROM ada', 'SELECT email FROM ada'];

        foreach ($queries as $query) {
            $result = $mysqli->query($query);

            foreach ($result as $row) {
                assertType('array{adaid: int<-32768, 32767>}|array{email: string}', $row);
            }
        }
    }

    public function mysqliResultfetch(mysqli $mysqli)
    {
        $result = mysqli_query($mysqli, 'SELECT adaid FROM ada');
        assertType('array{adaid: int<-32768, 32767>}|false|null', $result->fetch_assoc());

        $result = mysqli_query($mysqli, 'SELECT adaid FROM ada');
        assertType('list<array{int<-32768, 32767>}>', $result->fetch_all());
        assertType('list<array{adaid: int<-32768, 32767>}>', $result->fetch_all(MYSQLI_ASSOC));
        assertType('list<array{int<-32768, 32767>}>', $result->fetch_all(MYSQLI_NUM));
        assertType('list<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>}>', $result->fetch_all(MYSQLI_BOTH));
    }

    public function fetchAllColumns(mysqli $mysqli)
    {
        $result = $mysqli->query('SELECT email, adaid FROM ada');
        assertType('list<array{string, int<-32768, 32767>}>', $result->fetch_all());
        assertType('list<array{email: string, adaid: int<-32768, 32767>}>', $result->fetch_all(MYSQLI_ASSOC));
        assertType('list<array{string, int<-32768, 32767>}>', $result->fetch_all(MYSQLI_NUM));
        assertType('list<array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}>', $result->fetch_all(MYSQLI_BOTH));
        assertType('list<array{email: string, adaid: int<-32768, 32767>}>', $result->fetch_all(1));
        assertType('list<array{string, int<-32768, 32767>}>', $result->fetch_all(2));
        assertType('list<array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}>', $result->fetch_all(3));
        assertType('array{email: string, adaid: int<-32768, 32767>}|false|null', $result->fetch_assoc());
        foreach ($result as $row) {
            assertType('array{email: string, adaid: int<-32768, 32767>}', $row);
        }
    }

    public function fetchAllUnionResult(mysqli $mysqli)
    {
        $queries = ['SELECT adaid FROM ada', 'SELECT email FROM ada'];

        foreach ($queries as $query) {
            $result = $mysqli->query($query);
            assertType('list<array{int<-32768, 32767>}|array{string}>', $result->fetch_all());
            assertType('list<array{adaid: int<-32768, 32767>}|array{email: string}>', $result->fetch_all(MYSQLI_ASSOC));
            assertType('list<array{int<-32768, 32767>}|array{string}>', $result->fetch_all(MYSQLI_NUM));
            assertType('list<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>}|array{email: string, 0: string}>', $result->fetch_all(MYSQLI_BOTH));
            assertType('array{adaid: int<-32768, 32767>}|array{email: string}|false|null', $result->fetch_assoc());
        }
    }

    public function fetchAllUnionMode(mysqli $mysqli)
    {
        $result = $mysqli->query('SELECT adaid FROM ada');
        $mode = rand(0, 1) ? MYSQLI_ASSOC : MYSQLI_NUM;
        assertType('list<array{adaid: int<-32768, 32767>}|array{int<-32768, 32767>}>', $result->fetch_all($mode));
        assertType('list<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>}>', $result->fetch_all(MYSQLI_ASSOC | MYSQLI_NUM));
        assertType('list<array{adaid: int<-32768, 32767>}>', $result->fetch_all(MYSQLI_ASSOC));
    }

    public function fetchObject(mysqli $mysqli)
    {
        $result = mysqli_query($mysqli, 'SELECT email, adaid FROM ada');
        $row = $result->fetch_object();
        assertType('(object{email: string, adaid: int<-32768, 32767>}&stdClass)|false|null', $row);

        $row = $result->fetch_object('AdaClass1');
        assertType('AdaClass1|false|null', $row);

        $row = $result->fetch_object(rand() ? 'AdaClass1' : 'AdaClass2');
        assertType('AdaClass1|AdaClass2|false|null', $row);

        for($i = 1; $i < 3; $i++) {
            $row = $result->fetch_object('AdaClass' . $i);
            assertType('AdaClass1|AdaClass2|false|null', $row);
        }
    }

    public function fetchRow(mysqli $mysqli)
    {
        $result = mysqli_query($mysqli, 'SELECT email, adaid FROM ada');
        $row = $result->fetch_row();
        assertType('array{string, int<-32768, 32767>}|false|null', $row);
    }

    public function proceduralFetchAssoc(mysqli $mysqli)
    {
        $result = mysqli_query($mysqli, 'SELECT email, adaid FROM ada');
        $row = mysqli_fetch_assoc($result);
        assertType('array{email: string, adaid: int<-32768, 32767>}|false|null', $row);
    }

    public function proceduralFetchAllColumns(mysqli $mysqli)
    {
        $result = $mysqli->query('SELECT email, adaid FROM ada');
        assertType('list<array{string, int<-32768, 32767>}>', mysqli_fetch_all($result));
        assertType('list<array{email: string, adaid: int<-32768, 32767>}>', mysqli_fetch_all($result, MYSQLI_ASSOC));
        assertType('list<array{string, int<-32768, 32767>}>', mysqli_fetch_all($result, MYSQLI_NUM));
        assertType('list<array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}>', mysqli_fetch_all($result, MYSQLI_BOTH));
        assertType('list<array{email: string, adaid: int<-32768, 32767>}>', mysqli_fetch_all($result, 1));
        assertType('list<array{string, int<-32768, 32767>}>', mysqli_fetch_all($result, 2));
        assertType('list<array{email: string, 0: string, adaid: int<-32768, 32767>, 1: int<-32768, 32767>}>', mysqli_fetch_all($result, 3));
        assertType('array{email: string, adaid: int<-32768, 32767>}|false|null', mysqli_fetch_assoc($result));
        foreach ($result as $row) {
            assertType('array{email: string, adaid: int<-32768, 32767>}', $row);
        }
    }

    public function proceduralFetchAllUnionResult(mysqli $mysqli)
    {
        $queries = ['SELECT adaid FROM ada', 'SELECT email FROM ada'];

        foreach ($queries as $query) {
            $result = $mysqli->query($query);
            assertType('list<array{int<-32768, 32767>}|array{string}>', mysqli_fetch_all($result));
            assertType('list<array{adaid: int<-32768, 32767>}|array{email: string}>', mysqli_fetch_all($result, MYSQLI_ASSOC));
            assertType('list<array{int<-32768, 32767>}|array{string}>', mysqli_fetch_all($result,MYSQLI_NUM));
            assertType('list<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>}|array{email: string, 0: string}>', mysqli_fetch_all($result, MYSQLI_BOTH));
            assertType('array{adaid: int<-32768, 32767>}|array{email: string}|false|null', mysqli_fetch_assoc($result));
        }
    }

    public function proceduralFetchAllUnionMode(mysqli $mysqli)
    {
        $result = $mysqli->query('SELECT adaid FROM ada');
        $mode = rand(0, 1) ? MYSQLI_ASSOC : MYSQLI_NUM;
        assertType('list<array{adaid: int<-32768, 32767>}|array{int<-32768, 32767>}>', mysqli_fetch_all($result, $mode));
        assertType('list<array{adaid: int<-32768, 32767>, 0: int<-32768, 32767>}>', mysqli_fetch_all($result, MYSQLI_ASSOC | MYSQLI_NUM));
        assertType('list<array{adaid: int<-32768, 32767>}>', mysqli_fetch_all($result, MYSQLI_ASSOC));
    }

    public function proceduralFetchObject(mysqli $mysqli)
    {
        $result = mysqli_query($mysqli, 'SELECT email, adaid FROM ada');
        $row = mysqli_fetch_object($result);
        assertType('(object{email: string, adaid: int<-32768, 32767>}&stdClass)|false|null', $row);

        $row = mysqli_fetch_object($result, 'AdaClass1');
        assertType('AdaClass1|false|null', $row);

        $row = mysqli_fetch_object($result, rand() ? 'AdaClass1' : 'AdaClass2');
        assertType('AdaClass1|AdaClass2|false|null', $row);

        for($i = 1; $i < 3; $i++) {
            $row = mysqli_fetch_object($result, 'AdaClass' . $i);
            assertType('AdaClass1|AdaClass2|false|null', $row);
        }
    }

    public function proceduralFetchRow(mysqli $mysqli)
    {
        $result = mysqli_query($mysqli, 'SELECT email, adaid FROM ada');
        $row = mysqli_fetch_row($result);
        assertType('array{string, int<-32768, 32767>}|false|null', $row);
    }
}
