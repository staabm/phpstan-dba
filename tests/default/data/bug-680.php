<?php

namespace Bug680;

use Doctrine\DBAL\Connection;
use function PHPStan\Testing\assertType;

class Test
{
    private Connection $connection;

    public function doFoo(string $token): array
    {
        $content = $this
            ->connection
            ->fetchAssociative(
                '#cart-persister::load
                SELECT email, adaid FROM ada WHERE adaid = :token',
                ['token' => $token],
            );
        assertType('array{email: string, adaid: int<-32768, 32767>}|false', $content);
    }
}
