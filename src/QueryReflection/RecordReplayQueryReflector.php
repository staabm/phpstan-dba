<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use const LOCK_EX;
use PHPStan\Type\Type;

final class RecordReplayQueryReflector implements QueryReflector
{
    public const SCHEMA_VERSION = 'v1-initial';

    /**
     * @var string
     */
    private $cacheFile;

    /**
     * @var array<string, array{containsSyntaxErrors?: bool, result?: array<self::FETCH_TYPE*, ?Type>}>
     */
    private $records = [];

    /**
     * @var QueryReflector
     */
    private $reflector;

    public function __construct(string $cacheFile, QueryReflector $wrappedReflector)
    {
        $this->cacheFile = $cacheFile;
        $this->reflector = $wrappedReflector;

        if (is_readable($cacheFile)) {
            $cache = include $cacheFile;

            if (\is_array($cache) && \array_key_exists('schemaVersion', $cache) && self::SCHEMA_VERSION === $cache['schemaVersion']) {
                $this->records = $cache['records'];
            }
        }

        register_shutdown_function(function () {
            // sort records to prevent unnecessary cache invalidation caused by different order of queries
            ksort($this->records);

            file_put_contents($this->cacheFile, '<?php return '.var_export([
                'schemaVersion' => self::SCHEMA_VERSION,
                'records' => $this->records,
            ], true).';', LOCK_EX);
        });
    }

    public function containsSyntaxError(string $simulatedQueryString): bool
    {
        if (!\array_key_exists($simulatedQueryString, $this->records)) {
            $this->records[$simulatedQueryString] = [];
        }

        $cacheEntry = &$this->records[$simulatedQueryString];
        if (!\array_key_exists('containsSyntaxErrors', $cacheEntry)) {
            $cacheEntry['containsSyntaxErrors'] = $this->reflector->containsSyntaxError($simulatedQueryString);
        }

        return $cacheEntry['containsSyntaxErrors'];
    }

    public function getResultType(string $simulatedQueryString, int $fetchType): ?Type
    {
        if (!\array_key_exists($simulatedQueryString, $this->records)) {
            $this->records[$simulatedQueryString] = [];
        }

        $cacheEntry = &$this->records[$simulatedQueryString];
        if (!\array_key_exists('result', $cacheEntry)) {
            $cacheEntry['result'] = [];
        }

        if (!\array_key_exists($fetchType, $cacheEntry['result'])) {
            $cacheEntry['result'][$fetchType] = $this->reflector->getResultType($simulatedQueryString, $fetchType);
        }

        return $cacheEntry['result'][$fetchType];
    }
}
