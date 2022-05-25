<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\Type\Type;
use staabm\PHPStanDba\CacheNotPopulatedException;
use staabm\PHPStanDba\DbSchema\SchemaHasherMysql;
use staabm\PHPStanDba\Error;

final class ReplayAndRecordingQueryReflector implements QueryReflector, RecordingReflector
{
    private ReplayQueryReflector $replayReflector;

    private ?RecordingQueryReflector $recordingReflector = null;
    private QueryReflector $queryReflector;
    private ReflectionCache $reflectionCache;
    private SchemaHasherMysql $schemaHasher;

    public function __construct(ReflectionCache $reflectionCache, QueryReflector $queryReflector, SchemaHasherMysql $schemaHasher)
    {
        $this->replayReflector = new ReplayQueryReflector($reflectionCache);

        $this->queryReflector = $queryReflector;
        $this->schemaHasher = $schemaHasher;
        $this->reflectionCache = $reflectionCache;
    }

    private function dbSchemaChanged(): bool
    {
        $schemaHash = $this->schemaHasher->hashDb();
        $cachedSchemaHash = $this->reflectionCache->getSchemaHash();

        return $schemaHash !== $cachedSchemaHash;
    }

    private function createRecordingReflector(): RecordingQueryReflector
    {
        if (null === $this->recordingReflector) {
            $this->reflectionCache->setSchemaHash($this->schemaHasher->hashDb());
            $this->recordingReflector = new RecordingQueryReflector($this->reflectionCache, $this->queryReflector);
        }

        return $this->recordingReflector;
    }

    public function validateQueryString(string $queryString): ?Error
    {
        if ($this->dbSchemaChanged()) {
            return $this->createRecordingReflector()->validateQueryString($queryString);
        }

        try {
            return $this->replayReflector->validateQueryString($queryString);
        } catch (CacheNotPopulatedException $e) {
            return $this->createRecordingReflector()->validateQueryString($queryString);
        }
    }

    public function getResultType(string $queryString, int $fetchType): ?Type
    {
        if ($this->dbSchemaChanged()) {
            return $this->createRecordingReflector()->getResultType($queryString, $fetchType);
        }

        try {
            return $this->replayReflector->getResultType($queryString, $fetchType);
        } catch (CacheNotPopulatedException $e) {
            return $this->createRecordingReflector()->getResultType($queryString, $fetchType);
        }
    }

    public function getDatasource()
    {
        return $this->createRecordingReflector()->getDatasource();
    }
}
