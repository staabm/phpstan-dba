<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\Type\Type;
use staabm\PHPStanDba\CacheNotPopulatedException;
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

    private function getRecordingReflector(bool $forceCreate = false): ?RecordingQueryReflector
    {
        if (null === $this->recordingReflector)
        {
            if ($forceCreate === true ||
                $this->reflectionCache->getSchemaHash() !== $this->schemaHasher->hashDb())
            {
                $this->reflectionCache->setSchemaHash($this->schemaHasher->hashDb());
                $this->recordingReflector = new RecordingQueryReflector($this->reflectionCache, $this->queryReflector);
            }
        }

        return $this->recordingReflector;
    }

    public function validateQueryString(string $queryString): ?Error
    {
        $recordingReflector = $this->getRecordingReflector();
        if (null !== $recordingReflector) {
            return $recordingReflector->validateQueryString($queryString);
        }

        try {
            return $this->replayReflector->validateQueryString($queryString);
        } catch(CacheNotPopulatedException $e) {
            return $this->getRecordingReflector(true)->validateQueryString($queryString);
        }
    }

    public function getResultType(string $queryString, int $fetchType): ?Type
    {
        $recordingReflector = $this->getRecordingReflector();
        if (null !== $recordingReflector) {
            return $recordingReflector->getResultType($queryString, $fetchType);
        }

        try {
            return $this->replayReflector->getResultType($queryString, $fetchType);
        } catch (CacheNotPopulatedException $e) {
            return $this->getRecordingReflector(true)->getResultType($queryString, $fetchType);
        }
    }
}
