<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use const LOCK_EX;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Type;
use staabm\PHPStanDba\CacheNotPopulatedException;
use staabm\PHPStanDba\DbaException;
use staabm\PHPStanDba\Error;

final class ReflectionCache
{
    public const SCHEMA_VERSION = 'v9-put-null-when-valid';

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
     * @var string|null
     */
    private $schemaHash;

    /**
     * @var bool
     */
    private $cacheIsDirty = false;

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
                throw new DbaException(sprintf('Could not open lock file "%s".', $lockFile));
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
     * @return string|null
     */
    public function getSchemaHash()
    {
        if (null === $this->schemaHash) {
            $this->lazyReadRecords();
        }

        return $this->schemaHash;
    }

    /**
     * @return void
     */
    public function setSchemaHash(string $hash)
    {
        $this->cacheIsDirty = true;
        $this->schemaHash = $hash;
    }

    /**
     * @return array<string, array{error?: ?Error, result?: array<QueryReflector::FETCH_TYPE*, ?Type>}>
     */
    private function lazyReadRecords()
    {
        if ($this->initialized) {
            return $this->records;
        }

        $cachedRecords = $this->readCachedRecords(true);
        if (null !== $cachedRecords) {
            $this->records = $cachedRecords;
        } else {
            $this->records = [];
        }
        $this->initialized = true;

        return $this->records;
    }

    /**
     * @return array<string, array{error?: ?Error, result?: array<QueryReflector::FETCH_TYPE*, ?Type>}>|null
     */
    private function readCachedRecords(bool $useReadLock): ?array
    {
        if (!is_file($this->cacheFile)) {
            if (false === file_put_contents($this->cacheFile, '', LOCK_EX)) {
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

        if (!\is_array($cache) ||
            !\array_key_exists('schemaVersion', $cache) ||
            !\array_key_exists('schemaHash', $cache) ||
            self::SCHEMA_VERSION !== $cache['schemaVersion']) {
            return null;
        }

        if ($cache['runtimeConfig'] !== QueryReflection::getRuntimeConfiguration()->toArray()) {
            return null;
        }

        if (null === $this->schemaHash) {
            $this->schemaHash = $cache['schemaHash'];
        } elseif ($this->schemaHash !== $cache['schemaHash']) {
            return null;
        }

        if (!\is_array($cache['records'])) {
            throw new ShouldNotHappenException();
        }

        return $cache['records'];
    }

    public function persist(): void
    {
        if (false === $this->cacheIsDirty) {
            return;
        }

        try {
            flock(self::$lockHandle, LOCK_EX);

            // freshly read the cache as it might have changed in the meantime
            $cachedRecords = $this->readCachedRecords(false);

            // re-apply all changes to the current cache-state
            if (null === $cachedRecords) {
                $newRecords = $this->changes;
            } else {
                $newRecords = array_merge($cachedRecords, $this->changes);
            }

            // sort records to prevent unnecessary cache invalidation caused by different order of queries
            ksort($newRecords);

            foreach ($newRecords as &$newRecord) {
                ksort($newRecord);
            }

            unset($newRecord);

            $cacheContent = '<?php return '.var_export([
                    'schemaVersion' => self::SCHEMA_VERSION,
                    'schemaHash' => $this->schemaHash,
                    'records' => $newRecords,
                    'runtimeConfig' => QueryReflection::getRuntimeConfiguration()->toArray(),
                ], true).';';

            if (false === file_put_contents($this->cacheFile, $cacheContent, LOCK_EX)) {
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

    /**
     * @throws CacheNotPopulatedException
     */
    public function getValidationError(string $queryString): ?Error
    {
        $records = $this->lazyReadRecords();

        if (!\array_key_exists($queryString, $records)) {
            throw new CacheNotPopulatedException(sprintf('Cache not populated for query "%s"', $queryString));
        }

        $cacheEntry = $this->records[$queryString];
        if (!\array_key_exists('error', $cacheEntry)) {
            return null;
        }

        return $cacheEntry['error'];
    }

    public function putValidationError(string $queryString, ?Error $error): void
    {
        $records = $this->lazyReadRecords();

        if (!\array_key_exists($queryString, $records)) {
            $this->changes[$queryString] = $this->records[$queryString] = [];
            $this->cacheIsDirty = true;
        }

        if (!\array_key_exists('error', $this->records[$queryString]) || $this->records[$queryString]['error'] !== $error) {
            $this->changes[$queryString]['error'] = $this->records[$queryString]['error'] = $error;
            $this->cacheIsDirty = true;
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
     *
     * @throws CacheNotPopulatedException
     */
    public function getResultType(string $queryString, int $fetchType): ?Type
    {
        $records = $this->lazyReadRecords();

        if (!\array_key_exists($queryString, $records)) {
            throw new CacheNotPopulatedException(sprintf('Cache not populated for query "%s"', $queryString));
        }

        $cacheEntry = $this->records[$queryString];
        if (!\array_key_exists('result', $cacheEntry)) {
            throw new CacheNotPopulatedException(sprintf('Cache not populated for query "%s"', $queryString));
        }

        if (!\array_key_exists($fetchType, $cacheEntry['result'])) {
            throw new CacheNotPopulatedException(sprintf('Cache not populated for query "%s"', $queryString));
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
            $this->cacheIsDirty = true;
        }

        if (!\array_key_exists('result', $this->records[$queryString])) {
            $this->changes[$queryString]['result'] = $this->records[$queryString]['result'] = [];
            $this->cacheIsDirty = true;
        }

        // @phpstan-ignore-next-line
        if (!\array_key_exists($fetchType, $this->records[$queryString]['result']) || $this->records[$queryString]['result'][$fetchType] !== $resultType) {
            $this->changes[$queryString]['result'][$fetchType] = $this->records[$queryString]['result'][$fetchType] = $resultType;
            $this->cacheIsDirty = true;
        }
    }
}
