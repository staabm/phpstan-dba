<?php

namespace SyntaxErrorInQueryRuleTest;

use PDO;

class Foo
{
    public function syntaxError(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada', PDO::FETCH_ASSOC);
    }

    public function unknownColumn(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT doesNotExist, adaid, gesperrt, freigabe1u1 FROM ada', PDO::FETCH_ASSOC);
    }

	public function unknownWhereColumn(PDO $pdo)
	{
		$stmt = $pdo->query('SELECT * FROM ada WHERE doesNotExist=1', PDO::FETCH_ASSOC);
	}

	public function unknownOrderColumn(PDO $pdo)
	{
		$stmt = $pdo->query('SELECT * FROM ada ORDER BY doesNotExist', PDO::FETCH_ASSOC);
	}

	public function unknownGroupByColumn(PDO $pdo)
	{
		$stmt = $pdo->query('SELECT * FROM ada GROUP BY doesNotExist', PDO::FETCH_ASSOC);
	}

    public function unknownTable(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT * FROM unknownTable', PDO::FETCH_ASSOC);
    }

    public function validQuery(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT email, adaid, gesperrt, freigabe1u1 FROM ada', PDO::FETCH_ASSOC);
    }
}
