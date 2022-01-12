<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use const LOCK_EX;
use PHPStan\Type\Type;
use staabm\PHPStanDba\DbaException;
use staabm\PHPStanDba\Error;

final class ReflectionCache
{
    public const SCHEMA_VERSION = 'v3-rename-props';

    /**
     * @var string
     */
    private $cacheFile;

    /**
     * @var array<string, array{error?: ?Error, result?: array<QueryReflector::FETCH_TYPE*, ?Type>}>
     */
    private $records = [];

    /**
     * @var bool
     */
    private $changed = false;

    private function __construct(string $cacheFile)
    {
        $this->cacheFile = $cacheFile;

        if (!is_file($cacheFile)) {
            // enforce file creation
            $this->changed = true;
        }
    }

    public static function create(string $cacheFile): self
    {
        return new self($cacheFile);
    }

    public static function load(string $cacheFile): self
    {
        if (!is_readable($cacheFile)) {
            throw new DbaException(sprintf('Cache file "%s" is not readable', $cacheFile));
        }

        $cache = require $cacheFile;

        $reflectionCache = new self($cacheFile);
        if (\is_array($cache) && \array_key_exists('schemaVersion', $cache) && self::SCHEMA_VERSION === $cache['schemaVersion']) {
            $reflectionCache->records = $cache['records'];
        }

        return $reflectionCache;
    }

    public function persist(): void
    {
        if (!$this->changed) {
            return;
        }

        $records = $this->records;

        // sort records to prevent unnecessary cache invalidation caused by different order of queries
        uksort($records, function ($queryA, $queryB) {
            return $queryA <=> $queryB;
        });

        if (false === file_put_contents($this->cacheFile, '<?php return '.var_export([
            'schemaVersion' => self::SCHEMA_VERSION,
            'records' => $records,
        ], true).';', LOCK_EX)) {
            throw new DbaException(sprintf('Unable to write cache file "%s"', $this->cacheFile));
        }
    }

    public function hasValidationError(string $queryString): bool
    {
        if (!\array_key_exists($queryString, $this->records)) {
            return false;
        }

        $cacheEntry = $this->records[$queryString];

        return \array_key_exists('error', $cacheEntry);
    }

    public function getValidationError(string $queryString): ?Error
    {
        if (!\array_key_exists($queryString, $this->records)) {
            throw new DbaException(sprintf('Cache not populated for query "%s"', $queryString));
        }

        $cacheEntry = $this->records[$queryString];
        if (!\array_key_exists('error', $cacheEntry)) {
            throw new DbaException(sprintf('Cache not populated for query "%s"', $queryString));
        }

        return $cacheEntry['error'];
    }

    public function putValidationError(string $queryString, ?Error $error): void
    {
        if (!\array_key_exists($queryString, $this->records)) {
            $this->records[$queryString] = [];
            $this->changed = true;
        }

        $cacheEntry = &$this->records[$queryString];
        if (!\array_key_exists('error', $cacheEntry) || $cacheEntry['error'] !== $error) {
            $cacheEntry['error'] = $error;
            $this->changed = true;
        }
    }

    /**
     * @param QueryReflector::FETCH_TYPE* $fetchType
     */
    public function hasResultType(string $queryString, int $fetchType): bool
    {
        if (!\array_key_exists($queryString, $this->records)) {
            return false;
        }

        $cacheEntry = $this->records[$queryString];
        if (!\array_key_exists('result', $cacheEntry)) {
            return false;
        }

        return \array_key_exists($fetchType, $cacheEntry['result']);
    }

    /**
     * @param QueryReflector::FETCH_TYPE* $fetchType
     */
    public function getResultType(string $queryString, int $fetchType): ?Type
    {
        if (!\array_key_exists($queryString, $this->records)) {
            throw new DbaException(sprintf('Cache not populated for query "%s"', $queryString));
        }

        $cacheEntry = $this->records[$queryString];
        if (!\array_key_exists('result', $cacheEntry)) {
            throw new DbaException(sprintf('Cache not populated for query "%s"', $queryString));
        }

        if (!\array_key_exists($fetchType, $cacheEntry['result'])) {
            throw new DbaException(sprintf('Cache not populated for query "%s"', $queryString));
        }

        return $cacheEntry['result'][$fetchType];
    }

    /**
     * @param QueryReflector::FETCH_TYPE* $fetchType
     */
    public function putResultType(string $queryString, int $fetchType, ?Type $resultType): void
    {
        if (!\array_key_exists($queryString, $this->records)) {
            $this->records[$queryString] = [];
            $this->changed = true;
        }

        $cacheEntry = &$this->records[$queryString];
        if (!\array_key_exists('result', $cacheEntry)) {
            $cacheEntry['result'] = [];
            $this->changed = true;
        }

        if (!\array_key_exists($fetchType, $cacheEntry['result']) || $cacheEntry['result'][$fetchType] !== $resultType) {
            $cacheEntry['result'][$fetchType] = $resultType;
            $this->changed = true;
        }
    }
}
