<?php

namespace Bug680;

use Doctrine\DBAL\Connection;
use function PHPStan\Testing\assertType;

class Test
{
    private Connection $connection;

    public function doFoo(string $token): void
    {
        $content = $this
            ->connection
            ->fetchAssociative(
                '#cart-persister::load
                SELECT email FROM ada WHERE adaid = :token',
                ['token' => $token],
            );
        assertType('array{email: string}|false', $content);
    }
}
