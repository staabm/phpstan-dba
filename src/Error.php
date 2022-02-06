<?php

namespace staabm\PHPStanDba;

use staabm\PHPStanDba\QueryReflection\MysqliQueryReflector;
use staabm\PHPStanDba\QueryReflection\PDOQueryReflector;

final class Error
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var MysqliQueryReflector::MYSQL_*|PDOQueryReflector::PSQL_*
     */
    private $code;

    /**
     * @param MysqliQueryReflector::MYSQL_*|PDOQueryReflector::PSQL_* $code
     */
    public function __construct(string $message, int|string $code)
    {
        $this->message = $message;
        $this->code = $code;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return MysqliQueryReflector::MYSQL_*|PDOQueryReflector::PSQL_*
     */
    public function getCode(): int|string
    {
        return $this->code;
    }

    public function asRuleMessage(): string
    {
        return 'Query error: '.$this->getMessage().' ('.$this->getCode().').';
    }

    /**
     * @param array{message: string, code: MysqliQueryReflector::MYSQL_*|PDOQueryReflector::PSQL_*} $array
     */
    public static function __set_state(array $array)
    {
        return new self($array['message'], $array['code']);
    }
}
