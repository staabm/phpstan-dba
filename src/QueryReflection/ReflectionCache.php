<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use const LOCK_EX;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Type;
use staabm\PHPStanDba\DbaException;
use staabm\PHPStanDba\Error;

final class ReflectionCache
{
    public const SCHEMA_VERSION = 'v4-runtime-config';

    /**
     * @var string
     */
    private $cacheFile;

    /**
     * @var array<string, array{error?: ?Error, result?: array<QueryReflector::FETCH_TYPE*, ?Type>}>
     */
    private $records = [];

    /**
     * @var array<string, array{error?: ?Error, result?: array<QueryReflector::FETCH_TYPE*, ?Type>}>
     */
    private $changes = [];

    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * @var resource
     */
    private static $lockHandle;

    private function __construct(string $cacheFile)
    {
        $this->cacheFile = $cacheFile;

        if (null === self::$lockHandle) {
            // prevent parallel phpstan-worker-process from writing into the cache file at the same time
            // XXX we use a single system-wide lock file, which might get problematic if multiple users run phpstan on the same machine at the same time
            $lockFile = sys_get_temp_dir().'/staabm-phpstan-dba-cache.lock';
            $lockHandle = fopen($lockFile, 'w+');
            if (false === $lockHandle) {
                throw new DbaException(sprintf('Could not open lock file "%s".', $this->cacheFile));
            }

            self::$lockHandle = $lockHandle;
        }
    }

    public static function create(string $cacheFile): self
    {
        return new self($cacheFile);
    }

    /**
     * @deprecated use create() instead
     */
    public static function load(string $cacheFile): self
    {
        return new self($cacheFile);
    }

    /**
     * @return array<string, array{error?: ?Error, result?: array<QueryReflector::FETCH_TYPE*, ?Type>}>
     */
    private function lazyReadRecords()
    {
        if ($this->initialized) {
            return $this->records;
        }

        $cache = $this->readCache(true);
        if (null !== $cache && $cache['runtimeConfig'] === QueryReflection::getRuntimeConfiguration()->toArray()) {
            $this->records = $cache['records'];
        } else {
            $this->records = [];
        }
        $this->initialized = true;

        return $this->records;
    }

    /**
     * @return array{
     *     records: array<string, array{error?: ?Error, result?: array<QueryReflector::FETCH_TYPE*, ?Type>}>,
     *     runtimeConfig: array<string, scalar>,
     *     schemaVersion: string
     * }|null
     */
    private function readCache(bool $useReadLock): ?array
    {
        if (!is_file($this->cacheFile)) {
            if (false === file_put_contents($this->cacheFile, '')) {
                throw new DbaException(sprintf('Cache file "%s" is not readable and creating a new one did not succeed.', $this->cacheFile));
            }
        }

        try {
            if ($useReadLock) {
                flock(self::$lockHandle, \LOCK_SH);
            }
            $cache = require $this->cacheFile;
        } finally {
            if ($useReadLock) {
                flock(self::$lockHandle, \LOCK_UN);
            }
        }

        if (!\is_array($cache) || !\array_key_exists('schemaVersion', $cache) || self::SCHEMA_VERSION !== $cache['schemaVersion']) {
            return null;
        }

        if ($cache['runtimeConfig'] !== QueryReflection::getRuntimeConfiguration()->toArray()) {
            return null;
        }

        if (!\is_array($cache['records'])) {
            throw new ShouldNotHappenException();
        }

        // @phpstan-ignore-next-line
        return $cache;
    }

    public function persist(): void
    {
        if (0 === \count($this->changes)) {
            return;
        }

        try {
            flock(self::$lockHandle, LOCK_EX);

            // freshly read the cache as it might have changed in the meantime
            $cachedRecords = $this->readCache(false);

            // re-apply all changes to the current cache-state
            if (null === $cachedRecords) {
                $newRecords = $this->changes;
            } else {
                $newRecords = array_merge($cachedRecords, $this->changes);
            }

            // sort records to prevent unnecessary cache invalidation caused by different order of queries
            uksort($newRecords, function ($queryA, $queryB) {
                return $queryA <=> $queryB;
            });

            $cacheContent = '<?php return '.var_export([
                    'schemaVersion' => self::SCHEMA_VERSION,
                    'records' => $newRecords,
                    'runtimeConfig' => [QueryReflection::getRuntimeConfiguration()->toArray()],
                ], true).';';

            if (false === file_put_contents($this->cacheFile, $cacheContent)) {
                throw new DbaException(sprintf('Unable to write cache file "%s"', $this->cacheFile));
            }

            clearstatcache(true, $this->cacheFile);
            if (\function_exists('opcache_invalidate')) {
                opcache_invalidate($this->cacheFile, true);
            }
        } finally {
            flock(self::$lockHandle, \LOCK_UN);
        }
    }

    public function hasValidationError(string $queryString): bool
    {
        $records = $this->lazyReadRecords();

        if (!\array_key_exists($queryString, $records)) {
            return false;
        }

        $cacheEntry = $this->records[$queryString];

        return \array_key_exists('error', $cacheEntry);
    }

    public function getValidationError(string $queryString): ?Error
    {
        $records = $this->lazyReadRecords();

        if (!\array_key_exists($queryString, $records)) {
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
        $records = $this->lazyReadRecords();

        if (!\array_key_exists($queryString, $records)) {
            $this->changes[$queryString] = $this->records[$queryString] = [];
        }

        if (!\array_key_exists('error', $this->records[$queryString]) || $this->records[$queryString]['error'] !== $error) {
            $this->changes[$queryString]['error'] = $this->records[$queryString]['error'] = $error;
        }
    }

    /**
     * @param QueryReflector::FETCH_TYPE* $fetchType
     */
    public function hasResultType(string $queryString, int $fetchType): bool
    {
        $records = $this->lazyReadRecords();

        if (!\array_key_exists($queryString, $records)) {
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
        $records = $this->lazyReadRecords();

        if (!\array_key_exists($queryString, $records)) {
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
        $records = $this->lazyReadRecords();

        if (!\array_key_exists($queryString, $records)) {
            $this->changes[$queryString] = $this->records[$queryString] = [];
        }

        if (!\array_key_exists('result', $this->records[$queryString])) {
            $this->changes[$queryString]['result'] = $this->records[$queryString]['result'] = [];
        }

        // @phpstan-ignore-next-line
        if (!\array_key_exists($fetchType, $this->records[$queryString]['result']) || $this->records[$queryString]['result'][$fetchType] !== $resultType) {
            $this->changes[$queryString]['result'][$fetchType] = $this->records[$queryString]['result'][$fetchType] = $resultType;
        }
    }
}
