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
     * @var array<string, array{error?: ?Error, result?: array<QueryReflector::FETCH_TYPE*, ?Type>}>
     */
    private $changes = [];

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
        $reflectionCache = new self($cacheFile);
        $cachedRecords = $reflectionCache->readCache();
        if (null !== $cachedRecords) {
            $reflectionCache->records = $cachedRecords;
        }

        return $reflectionCache;
    }

    /**
     * @return array<string, array{error?: ?Error, result?: array<QueryReflector::FETCH_TYPE*, ?Type>}>|null
     */
    private function readCache(): ?array
    {
        if (!is_file($this->cacheFile)) {
            if (false === file_put_contents($this->cacheFile, '')) {
                throw new DbaException(sprintf('Cache file "%s" is not readable and creating a new one did not succeed.', $this->cacheFile));
            }
        }
        clearstatcache(true, $this->cacheFile);
        $cache = require $this->cacheFile;

        if (\is_array($cache) && \array_key_exists('schemaVersion', $cache) && self::SCHEMA_VERSION === $cache['schemaVersion']) {
            return $cache['records'];
        }

        return null;
    }

    public function persist(): void
    {
        if (0 === \count($this->changes)) {
            return;
        }

        // prevent parallel phpstan-worker-process from writing into the cache file at the same time
        // XXX we use a single system-wide lock file, which might get problematic if multiple users run phpstan on the same machine at the same time
        $lockFile = sys_get_temp_dir().'/staabm-phpstan-dba-cache.lock';
        $lockHandle = fopen($lockFile, 'w+');
        if (false === $lockHandle) {
            throw new DbaException(sprintf('Could not open cache file "%s" for writing', $this->cacheFile));
        }
        flock($lockHandle, LOCK_EX);

        // freshly read the cache as it might have changed in the meantime
        $cachedRecords = $this->readCache();

        $handle = fopen($this->cacheFile, 'w+');
        if (false === $handle) {
            throw new DbaException(sprintf('Could not open cache file "%s" for writing', $this->cacheFile));
        }

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
            ], true).';';

        if (false === fwrite($handle, $cacheContent)) {
            throw new DbaException(sprintf('Unable to write cache file "%s"', $this->cacheFile));
        }

        fclose($handle);
        // will free the lock implictly
        fclose($lockHandle);
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
