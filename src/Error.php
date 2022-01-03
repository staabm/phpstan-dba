<?php

namespace staabm\PHPStanDba;

final class Error
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var MysqliQueryReflector::MYSQL_* $code
     */
    private $code;

	/**
	 * @param MysqliQueryReflector::MYSQL_* $code
	 */
    public function __construct(string $message, int $code)
    {
        $this->message = $message;
        $this->code = $code;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

	/**
	 * @return MysqliQueryReflector::MYSQL_*
	 */
    public function getCode(): int
    {
        return $this->code;
    }
}
