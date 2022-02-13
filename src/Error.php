<?php

namespace staabm\PHPStanDba;

use PHPStan\Type\ErrorType;
use staabm\PHPStanDba\QueryReflection\MysqliQueryReflector;
use staabm\PHPStanDba\QueryReflection\PdoQueryReflector;
use staabm\PHPStanDba\QueryReflection\QuerySimulation;

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
     * @param ErrorCodes $code
     */
    static public function forSyntaxError(\Throwable $exception, $code, string $queryString): self
    {
        $message = $exception->getMessage();

        // make error string consistent across mysql/mariadb
        $message = str_replace(' MySQL server', ' MySQL/MariaDB server', $message);
        $message = str_replace(' MariaDB server', ' MySQL/MariaDB server', $message);

        // to ease debugging, print the error we simulated
        $simulatedQuery = QuerySimulation::simulate($queryString);
        $message = $message."\n\nSimulated query: ".$simulatedQuery;

        return new self($message, $code);
    }

    /**
     * @param ErrorCodes $code
     */
    static public function forException(\Throwable $exception, $code): self
    {
        $message = $exception->getMessage();

        // make error string consistent across mysql/mariadb
        $message = str_replace(' MySQL server', ' MySQL/MariaDB server', $message);
        $message = str_replace(' MariaDB server', ' MySQL/MariaDB server', $message);

        return new self($message, $code);
    }

    /**
     * @param array{message: string, code: ErrorCodes} $array
     */
    public static function __set_state(array $array)
    {
        return new self($array['message'], $array['code']);
    }
}
