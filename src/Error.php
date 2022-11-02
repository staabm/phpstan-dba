<?php

namespace staabm\PHPStanDba;

use PDOException;
use staabm\PHPStanDba\QueryReflection\BasePdoQueryReflector;
use staabm\PHPStanDba\QueryReflection\MysqliQueryReflector;
use staabm\PHPStanDba\QueryReflection\QuerySimulation;

/**
 * @phpstan-type ExtensionErrorCode value-of<MysqliQueryReflector::MYSQL_ERROR_CODES>|value-of<BasePdoQueryReflector::PDO_ERROR_CODES>
 * @phpstan-type MysqlErrorCode value-of<MysqliQueryReflector::MYSQL_ERROR_CODES>
 */
final class Error
{
    /**
     * @var string
     */
    private $message;

    /**
     * The error code of the used php extension.
     *
     * Sometimes thats the same as the dbErrorCode,
     * but some extensions (e.g. PDO) have separate error codes.
     *
     * @var ExtensionErrorCode
     */
    private $extensionErrorCode;

    /**
     * The underlying mysql error code.
     *
     * @var MysqlErrorCode|null
     */
    private $dbErrorCode;

    /**
     * @param ExtensionErrorCode $extensionErrorCode
     * @param MysqlErrorCode     $dbErrorCode
     */
    public function __construct(string $message, $extensionErrorCode, $dbErrorCode)
    {
        $this->message = $message;
        $this->extensionErrorCode = $extensionErrorCode;
        $this->dbErrorCode = $dbErrorCode;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return ExtensionErrorCode
     */
    public function getCode()
    {
        return $this->extensionErrorCode;
    }

    public function isInsignificant(): bool
    {
        $noTableGiven = strpos($this->getMessage(), " ''");
        $incorrectTable = \in_array($this->dbErrorCode, [MysqliQueryReflector::MYSQL_INCORRECT_TABLE], true);

        return $incorrectTable && $noTableGiven;
    }

    public function asRuleMessage(): string
    {
        return 'Query error: '.$this->getMessage().' ('.$this->getCode().').';
    }

    /**
     * @param ExtensionErrorCode $code
     */
    public static function forSyntaxError(\Throwable $exception, $code, string $queryString): self
    {
        if ($exception instanceof PDOException && \is_array($exception->errorInfo)) {
            $dbErrorCode = $exception->errorInfo[1];
        } else {
            $dbErrorCode = $code;
        }

        $message = $exception->getMessage();
        // make error string consistent across mysql/mariadb
        $message = str_replace(' MySQL server', ' MySQL/MariaDB server', $message);
        $message = str_replace(' MariaDB server', ' MySQL/MariaDB server', $message);

        // to ease debugging, print the error we simulated
        $simulatedQuery = QuerySimulation::simulate($queryString);
        $message = $message."\n\nSimulated query: ".$simulatedQuery;

        return new self($message, $code, $dbErrorCode);
    }

    /**
     * @param ExtensionErrorCode $code
     */
    public static function forException(\Throwable $exception, $code): self
    {
        if ($exception instanceof PDOException && \is_array($exception->errorInfo)) {
            $dbErrorCode = $exception->errorInfo[1];
        } else {
            $dbErrorCode = $code;
        }

        $message = $exception->getMessage();
        // make error string consistent across mysql/mariadb
        $message = str_replace(' MySQL server', ' MySQL/MariaDB server', $message);
        $message = str_replace(' MariaDB server', ' MySQL/MariaDB server', $message);

        return new self($message, $code, $dbErrorCode);
    }

    /**
     * @param array{message: string, extensionErrorCode: ExtensionErrorCode, dbErrorCode: MysqlErrorCode} $array
     */
    public static function __set_state(array $array)
    {
        return new self($array['message'], $array['extensionErrorCode'], $array['dbErrorCode']);
    }
}
