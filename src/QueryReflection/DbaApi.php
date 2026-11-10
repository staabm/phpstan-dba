<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

final class DbaApi
{
    public const API_DIBI = 'dibi';

    /**
     * @var self::API_*
     */
    private $api;

    /**
     * @param self::API_* $api
     */
    public function __construct($api)
    {
        $this->api = $api;
    }

    public function returnsDateTimeImmutable(): bool
    {
        return self::API_DIBI === $this->api;
    }
}
