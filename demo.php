<?php

function demo(\PDO $pdo): void
{
	$stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
	$stmt->execute([':wrongParamName' => 1]);

	$stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
	$stmt->execute(); // missing parameter

	$stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = ? and email = ?');
	$stmt->execute([1]); // wrong number of parameters

	$stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid and email = :email');
	$stmt->execute(['adaid' => 1]); // wrong number of parameters

	$stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid and email = :email');
	$stmt->execute([':email' => 'email@example.org']); // wrong number of parameters
}
