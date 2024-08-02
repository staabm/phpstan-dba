<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Tests;

use PHPUnit\Framework\TestCase;
use staabm\PHPStanDba\QueryReflection\PdoMysqlQueryReflector;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\RuntimeConfiguration;

class AnsiQuotesSupportTest extends TestCase
{
    public function testAnsiQuotesSupport(): void
    {
        $pdo = new \PDO(...);
        QueryReflection::setupReflector(new PdoMysqlQueryReflector($pdo), new RuntimeConfiguration());

        $this->analyse([__DIR__ . '/data/bug-668.php'], []);

        $pdo = new \PDO(...);
        $pdo->prepare('SET sql_mode = ANSI_QUOTES;')->execute();
        QueryReflection::setupReflector(new PdoMysqlQueryReflector($pdo), new RuntimeConfiguration());

        $this->expectException(\Exception::class);

        $this->analyse([__DIR__ . '/data/bug-668.php'], []);
    }
}
