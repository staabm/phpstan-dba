<?php

function demo(\PDO $pdo): void
{
	$pdo->query('SELECT * FROM unknownTable', PDO::FETCH_ASSOC);

	$pdo->query('SELECT email adaid WHERE gesperrt freigabe1u1 FROM ada', PDO::FETCH_ASSOC);

	$pdo->query('SELECT doesNotExist, adaid, gesperrt, freigabe1u1 FROM ada', PDO::FETCH_ASSOC);

	$stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
	$stmt->execute([':wrongParamName' => 1]);

	$stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
	$stmt->execute();

	$stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = ? and email = ?');
	$stmt->execute([1]);

	$stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid and email = :email');
	$stmt->execute(['adaid' => 1]);

	$stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid and email = :email');
	$stmt->execute([':email' => 'email@example.org']);
}
