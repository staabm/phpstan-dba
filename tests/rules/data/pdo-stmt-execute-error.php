<?php

namespace PdoStatementExecuteErrorMethodRuleTest;

use PDO;

class Foo
{
    public function errors(PDO $pdo)
    {
        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
        $stmt->execute(['wrongParamName' => 1]);

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
        $stmt->execute([':wrongParamName' => 1]);

        // $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
        // $stmt->execute([':adaid' => 'hello world']); // wrong param type

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
        $stmt->execute(); // missing parameter

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = ? and email = ?');
        $stmt->execute([1]); // wrong number of parameters

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid and email = :email');
        $stmt->execute(['adaid' => 1]); // wrong number of parameters

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid and email = :email');
        $stmt->execute([':email' => 'email@example.org']); // wrong number of parameters
    }

    public function multi_execute(PDO $pdo)
    {
        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
        $stmt->execute(['wrongParamName' => 1]); // wrong param name
        $stmt->execute(['adaid' => 1]); // everything correct
        $stmt->execute([':email' => 'email@example.org', 'adaid' => 1]); // wrong number of parameters
    }

    public function conditionalSyntaxError(PDO $pdo)
    {
        $query = 'SELECT email, adaid, gesperrt, freigabe1u1 FROM ada';

        if (rand(0, 1)) {
            // valid condition
            $query .= ' WHERE adaid = :adaid';
        } else {
            // wrong param name
            $query .= ' WHERE adaid = :asdsa';
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute(['adaid' => 1]);
    }

    public function errorsBind(PDO $pdo)
    {
        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
        $stmt->bindValue('wrongParamName', 1);
        $stmt->execute();

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
        $stmt->bindValue(':wrongParamName', 1);
        $stmt->execute();

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid and email = :email');
        $stmt->bindValue(':adaid', 1);
        $stmt->execute(); // wrong number of parameters

        $stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid and email = :email');
        $stmt->bindValue(':email', 'email@example.org');
        $stmt->execute(); // wrong number of parameters
    }

    public function bug336(PDO $pdo)
    {
        $stmt = $pdo->prepare('
            SELECT
                ada.*,
                COALESCE(NULLIF(email, ""), email) AS email
            FROM ada
                INNER JOIN ak ON (ak.adaid = ada.adaid AND ak.akid = ?)
            WHERE adaid = 1 ORDER BY COALESCE(NULLIF(email, ""), email) ASC
        ');
        $stmt->execute([1]); // everything fine
    }

    public function bug336Named(PDO $pdo)
    {
        $stmt = $pdo->prepare('
            SELECT
                ada.*,
                COALESCE(NULLIF(email, ""), email) AS email
            FROM ada
                INNER JOIN ak ON (ak.adaid = ada.adaid AND ak.akid = :akid)
            WHERE adaid = 1 ORDER BY COALESCE(NULLIF(email, ""), email) ASC
        ');
        $stmt->execute([':akid' => 1]); // everything fine
    }

    public function bug422UnnamedPlaceholdersInComment(PDO $pdo)
    {
        $query = '
            Select * from ada
            where adakzid = 15 -- customer ???
        ';

        $stmt = $pdo->prepare($query);
        $stmt->execute([]);
    }

    public function bug422NamedPlaceholdersInComment(PDO $pdo)
    {
        $stmt = $pdo->prepare('SELECT email, adaid FROM ada -- nice :placeholder bro');
        $stmt->execute([]);
    }

    public function bug422NamedAndUnnamedPlaceholdersInComment(PDO $pdo)
    {
        $stmt = $pdo->prepare('SELECT email, adaid FROM ada -- :can have :more ? :placeholders? ?');
        $stmt->execute([]);
    }

    public function bug422UnnamedPlaceholdersInCommentInsideOfQuery(PDO $pdo)
    {
        $stmt = $pdo->prepare('SELECT email, adaid /* why? ? */ FROM ada /* just ?? :because ?*/ WHERE email = :email -- ?');
        $stmt->execute(['email' => 'a']);
    }

    public function supportNestedQuotes(PDO $pdo)
    {
        $stmt = $pdo->prepare(<<<SQL
            SELECT payload ->> '$."dash-separated"' = :value FROM ada WHERE 'foo'
            SQL
        );
        $stmt->execute(['value' => 'bar']);
    }
}
