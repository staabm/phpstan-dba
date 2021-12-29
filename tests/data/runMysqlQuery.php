<?php

namespace Deployer;

use function PHPStan\Testing\assertType;

/**
 * Allows to exectue a sql query on the remote database using the given access credentials.
 *
 * @param string $query
 *
 * @return string[][]|null A array containing the result of a SELECT query, or null on writable queries
 * @phpstan-return list<list<string>>|null
 */
function runMysqlQuery($query, DbCredentials $dbCredentials)
{
}

class DbCredentials
{
}

class Foo {
	public function run(string $prodHostname, DbCredentials $dbCredentials)
	{
		$prodDomains = runMysqlQuery('SELECT cmsdomainid FROM cmsdomain WHERE url ="'.$prodHostname.'" and standard=1', $dbCredentials);
		assertType('array<int, array{string}>|null', $prodDomains);
	}
}
