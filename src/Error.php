<?php

namespace staabm\PHPStanDba;

use staabm\PHPStanDba\QueryReflection\MysqliQueryReflector;
use staabm\PHPStanDba\QueryReflection\PdoQueryReflector;

/**
 * @phpstan-type ErrorCodes value-of<MysqliQueryReflector::MYSQL_ERROR_CODES>|value-of<PDOQueryReflector::PDO_ERROR_CODES>
 */
final class Error
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var ErrorCodes
     */
    private $code;

    /**
     * @param ErrorCodes $code
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
     * @return ErrorCodes
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
     * @param array{message: string, code: ErrorCodes} $array
     */
    public static function __set_state(array $array)
    {
        return new self($array['message'], $array['code']);
    }
}
