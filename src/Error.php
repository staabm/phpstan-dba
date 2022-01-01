<?php

namespace staabm\PHPStanDba;

final class Error
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var int
     */
    private $code;

    public function __construct(string $message, int $code)
    {
        $this->message = $message;
        $this->code = $code;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getCode(): int
    {
        return $this->code;
    }
}
