<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\Php\PhpVersion;
use staabm\PHPStanDba\Analyzer\QueryPlanAnalyzer;

/**
 * @api
 */
final class RuntimeConfiguration
{
    /**
     * methods should return `false` on error.
     */
    public const ERROR_MODE_BOOL = 'bool';

    /**
     * methods will throw exceptions on errors, therefore will never return `false`.
     */
    public const ERROR_MODE_EXCEPTION = 'exception';

    /**
     * use whatever the configured php-src version uses per default.
     *
     * @see https://phpstan.org/config-reference#phpversion
     */
    public const ERROR_MODE_DEFAULT = 'default';

    /**
     * @var self::ERROR_MODE*
     */
    private $errorMode = self::ERROR_MODE_DEFAULT;

    /**
     * @var QueryReflector::FETCH_TYPE*
     */
    private $defaultFetchMode = QueryReflector::FETCH_TYPE_BOTH;

    /**
     * @var bool
     */
    private $debugMode = false;

    /**
     * @var bool
     */
    private $stringifyTypes = false;

    /**
     * @var bool
     */
    private $writableQueries = true;

    /**
     * @var bool
     */
    private $utilizeSqlAst = false;

    /**
     * @var bool|0|positive-int
     */
    private $numberOfAllowedUnindexedReads = false;

    /**
     * @var false|0|positive-int
     */
    private $numberOfRowsNotRequiringIndex = false;

    public static function create(): self
    {
        return new self();
    }

    /**
     * Defines whether the database access returns `false` on error or throws exceptions.
     *
     * @param self::ERROR_MODE* $mode
     */
    public function errorMode(string $mode): self
    {
        $this->errorMode = $mode;

        return $this;
    }

    /**
     * Defines the PDO default fetch mode.
     * This might be necessary in case you are using `\PDO::ATTR_DEFAULT_FETCH_MODE`.
     *
     * @param QueryReflector::FETCH_TYPE_* $mode
     */
    public function defaultFetchMode(int $mode): self
    {
        $this->defaultFetchMode = $mode;

        return $this;
    }

    /**
     * When enabled, a DbaException will be thrown in case a sql query cannot be analyzed.
     *
     * Otherwise these queries will be ignored.
     *
     * @return $this
     */
    public function debugMode(bool $mode): self
    {
        $this->debugMode = $mode;

        return $this;
    }

    /**
     * Infer string-types instead of more precise types.
     * This might be necessary in case your are using `\PDO::ATTR_EMULATE_PREPARES` or `\PDO::ATTR_STRINGIFY_FETCHES`.
     */
    public function stringifyTypes(bool $stringify): self
    {
        $this->stringifyTypes = $stringify;

        return $this;
    }

    /**
     * Enables checking of writable queries (INSERT, UPDATE, DELETE,...).
     */
    public function analyzeWriteQueries(bool $enabled): self
    {
        $this->writableQueries = $enabled;

        return $this;
    }

    /**
     * Enables experimental sql-ast type narrowing.
     *
     * Requires "sqlftw/sqlftw" to be additionally installed.
     */
    public function utilizeSqlAst(bool $enabled): self
    {
        $this->utilizeSqlAst = $enabled;

        return $this;
    }

    /**
     * Enables query plan analysis, which indicates performance problems.
     *
     * Requires a active database connection.
     *
     * @param bool|0|positive-int $numberOfAllowedUnindexedReads `true` to enable analysis with QueryPlanAnalyzer::DEFAULT_UNINDEXED_READS_THRESHOLD. `false` to disable analysis.
     *                                                           Otherwise the number of reads a query is allowed to execute, before it is considered inefficient.
     *                                                           `0` disables the efficiency checks but still scans for queries not using an index.
     * @param 0|positive-int      $numberOfRowsNotRequiringIndex number of reads a query is allowed to execute, without requiring a index.
     *                                                           for sane defaults see QueryPlanAnalyzer::TABLES_WITHOUT_DATA and QueryPlanAnalyzer::DEFAULT_SMALL_TABLE_THRESHOLD
     *
     * @return $this
     */
    public function analyzeQueryPlans($numberOfAllowedUnindexedReads = true, $numberOfRowsNotRequiringIndex = QueryPlanAnalyzer::TABLES_WITHOUT_DATA): self
    {
        $this->numberOfAllowedUnindexedReads = $numberOfAllowedUnindexedReads;
        $this->numberOfRowsNotRequiringIndex = $numberOfRowsNotRequiringIndex;

        return $this;
    }

    /**
     * @return bool|0|positive-int
     */
    public function getNumberOfAllowedUnindexedReads()
    {
        return $this->numberOfAllowedUnindexedReads;
    }

    /**
     * @return false|0|positive-int
     */
    public function getNumberOfRowsNotRequiringIndex()
    {
        return $this->numberOfRowsNotRequiringIndex;
    }

    public function isDebugEnabled(): bool
    {
        return $this->debugMode;
    }

    public function isStringifyTypes(): bool
    {
        return $this->stringifyTypes;
    }

    public function isAnalyzingWriteQueries(): bool
    {
        return $this->writableQueries;
    }

    public function isUtilizingSqlAst(): bool
    {
        return $this->utilizeSqlAst;
    }

    /**
     * @return QueryReflector::FETCH_TYPE*
     */
    public function getDefaultFetchMode(): int
    {
        return $this->defaultFetchMode;
    }

    public function throwsPdoExceptions(PhpVersion $phpVersion): bool
    {
        if (self::ERROR_MODE_EXCEPTION === $this->errorMode) {
            return true;
        }
        if (self::ERROR_MODE_BOOL === $this->errorMode) {
            return false;
        }

        // since php8 the pdo php-src default error mode changed to exception
        return $phpVersion->getVersionId() >= 80000;
    }

    public function throwsMysqliExceptions(PhpVersion $phpVersion): bool
    {
        if (self::ERROR_MODE_EXCEPTION === $this->errorMode) {
            return true;
        }
        if (self::ERROR_MODE_BOOL === $this->errorMode) {
            return false;
        }

        // since php8.1 the mysqli php-src default error mode changed to exception
        return $phpVersion->getVersionId() >= 80100;
    }

    /**
     * @return array<string, scalar>
     */
    public function toArray(): array
    {
        return [
            'errorMode' => $this->errorMode,
            'debugMode' => $this->debugMode,
            'stringifyTypes' => $this->stringifyTypes,
        ];
    }
}
