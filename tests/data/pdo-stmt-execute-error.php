<?php

namespace SyntaxErrorInQueryMethodRuleTest;

use PDO;
use function PHPStan\Testing\assertType;

class Foo
{
	public function errors(PDO $pdo)
	{
		$stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
		$stmt->execute([':wrongParamName' => 1]);

		$stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
		$stmt->execute([':wrongParamValue' => 'hello world']);

		$stmt = $pdo->prepare('SELECT email, adaid FROM ada WHERE adaid = :adaid');
		$stmt->execute(); // missing parameter
	}
}
