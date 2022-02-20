<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\Type\Type;
use staabm\PHPStanDba\DbSchema\SchemaHasherMysql;
use staabm\PHPStanDba\Error;

final class ReplayAndRecordingQueryReflector implements QueryReflector
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

    private function getRecordingReflector(): ?RecordingQueryReflector
    {
        if (null === $this->recordingReflector && $this->reflectionCache->getSchemaHash() !== $this->schemaHasher->hashDb()) {
            $this->reflectionCache->setSchemaHash($this->schemaHasher->hashDb());

            $this->recordingReflector = new RecordingQueryReflector($this->reflectionCache, $this->queryReflector);
        }

        return $this->recordingReflector;
    }

    public function validateQueryString(string $queryString): ?Error
    {
        $recordingReflector = $this->getRecordingReflector();
        if (null !== $recordingReflector) {
            return $recordingReflector->validateQueryString($queryString);
        }

        return $this->replayReflector->validateQueryString($queryString);
    }

    public function getResultType(string $queryString, int $fetchType): ?Type
    {
        $recordingReflector = $this->getRecordingReflector();
        if (null !== $recordingReflector) {
            return $recordingReflector->getResultType($queryString, $fetchType);
        }

        return $this->replayReflector->getResultType($queryString, $fetchType);
    }
}
