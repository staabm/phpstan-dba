<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\Type\Type;
use staabm\PHPStanDba\DbSchema\SchemaHasher;
use staabm\PHPStanDba\Error;

final class ReplayAndRecordingQueryReflector implements QueryReflector, RecordingReflector
{
    /**
     * @var ReplayQueryReflector
     */
    private $replayReflector;

    /**
     * @var RecordingQueryReflector|null
     */
    private $recordingReflector = null;

    /**
     * @var QueryReflector
     */
    private $queryReflector;

    /**
     * @var ReflectionCache
     */
    private $reflectionCache;

    /**
     * @var SchemaHasher
     */
    private $schemaHasher;

    public function __construct(ReflectionCache $reflectionCache, QueryReflector $queryReflector, SchemaHasher $schemaHasher)
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

        $error = $this->replayReflector->validateQueryString($queryString);
        if (null !== $error) {
            return $error;
        }

        return $this->createRecordingReflector()->validateQueryString($queryString);
    }

    public function getResultType(string $queryString, int $fetchType): ?Type
    {
        if ($this->dbSchemaChanged()) {
            return $this->createRecordingReflector()->getResultType($queryString, $fetchType);
        }

        $resultType = $this->replayReflector->getResultType($queryString, $fetchType);
        if (null !== $resultType) {
            return $resultType;
        }

        return $this->createRecordingReflector()->getResultType($queryString, $fetchType);
    }

    public function setupDbaApi(?DbaApi $dbaApi): void
    {
        $this->queryReflector->setupDbaApi($dbaApi);
    }

    public function getDatasource()
    {
        return $this->createRecordingReflector()->getDatasource();
    }
}
