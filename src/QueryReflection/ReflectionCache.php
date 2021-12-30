<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use const LOCK_EX;
use PHPStan\Type\Type;
use staabm\PHPStanDba\DbaException;

final class ReflectionCache
{
    public const SCHEMA_VERSION = 'v1-initial';

    /**
     * @var string
     */
    private $cacheFile;

    /**
     * @var array<string, array{containsSyntaxErrors?: bool, result?: array<QueryReflector::FETCH_TYPE*, ?Type>}>
     */
    private $records = [];

    private function __construct(string $cacheFile)
    {
        $this->cacheFile = $cacheFile;
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

        $cache = include $cacheFile;

        $reflectionCache = new self($cacheFile);
        if (\is_array($cache) && \array_key_exists('schemaVersion', $cache) && self::SCHEMA_VERSION === $cache['schemaVersion']) {
            $reflectionCache->records = $cache['records'];
        }

        return $reflectionCache;
    }

    public function persist(): void
    {
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

    public function hasContainsSyntaxError(string $simulatedQueryString): bool
    {
        if (!\array_key_exists($simulatedQueryString, $this->records)) {
            return false;
        }

        $cacheEntry = $this->records[$simulatedQueryString];

        return \array_key_exists('containsSyntaxErrors', $cacheEntry);
    }

    public function getContainsSyntaxError(string $simulatedQueryString): bool
    {
        if (!\array_key_exists($simulatedQueryString, $this->records)) {
            throw new DbaException(sprintf('Cache not populated for query "%s"', $simulatedQueryString));
        }

        $cacheEntry = $this->records[$simulatedQueryString];
        if (!\array_key_exists('containsSyntaxErrors', $cacheEntry)) {
            throw new DbaException(sprintf('Cache not populated for query "%s"', $simulatedQueryString));
        }

        return $cacheEntry['containsSyntaxErrors'];
    }

    public function putContainsSyntaxError(string $simulatedQueryString, bool $containsSyntaxError): void
    {
        if (!\array_key_exists($simulatedQueryString, $this->records)) {
            $this->records[$simulatedQueryString] = [];
        }

        $cacheEntry = &$this->records[$simulatedQueryString];
        $cacheEntry['containsSyntaxErrors'] = $containsSyntaxError;
    }

    /**
     * @param QueryReflector::FETCH_TYPE* $fetchType
     */
    public function hasResultType(string $simulatedQueryString, int $fetchType): bool
    {
        if (!\array_key_exists($simulatedQueryString, $this->records)) {
            return false;
        }

        $cacheEntry = $this->records[$simulatedQueryString];
        if (!\array_key_exists('result', $cacheEntry)) {
            return false;
        }

        return \array_key_exists($fetchType, $cacheEntry['result']);
    }

    /**
     * @param QueryReflector::FETCH_TYPE* $fetchType
     */
    public function getResultType(string $simulatedQueryString, int $fetchType): ?Type
    {
        if (!\array_key_exists($simulatedQueryString, $this->records)) {
            throw new DbaException(sprintf('Cache not populated for query "%s"', $simulatedQueryString));
        }

        $cacheEntry = $this->records[$simulatedQueryString];
        if (!\array_key_exists('result', $cacheEntry)) {
            throw new DbaException(sprintf('Cache not populated for query "%s"', $simulatedQueryString));
        }

        if (!\array_key_exists($fetchType, $cacheEntry['result'])) {
            throw new DbaException(sprintf('Cache not populated for query "%s"', $simulatedQueryString));
        }

        return $cacheEntry['result'][$fetchType];
    }

    /**
     * @param QueryReflector::FETCH_TYPE* $fetchType
     */
    public function putResultType(string $simulatedQueryString, int $fetchType, ?Type $resultType): void
    {
        if (!\array_key_exists($simulatedQueryString, $this->records)) {
            $this->records[$simulatedQueryString] = [];
        }

        $cacheEntry = &$this->records[$simulatedQueryString];
        if (!\array_key_exists('result', $cacheEntry)) {
            $cacheEntry['result'] = [];
        }

        $cacheEntry['result'][$fetchType] = $resultType;
    }
}
