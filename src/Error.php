<?php

namespace staabm\PHPStanDba;

use staabm\PHPStanDba\QueryReflection\MysqliQueryReflector;

final class Error
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var MysqliQueryReflector::MYSQL_*
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

    public function asRuleMessage(): string
    {
        return 'Query error: '.$this->getMessage().' ('.$this->getCode().').';
    }

    /**
     * @param array{message: string, code: MysqliQueryReflector::MYSQL_*} $array
     */
    public static function __set_state(array $array)
    {
        return new self($array['message'], $array['code']);
    }
}
