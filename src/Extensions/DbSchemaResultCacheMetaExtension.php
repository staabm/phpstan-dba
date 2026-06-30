<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PHPStan\Analyser\ResultCache\ResultCacheMetaExtension;
use staabm\PHPStanDba\DbaException;
use staabm\PHPStanDba\QueryReflection\QueryReflection;

final class DbSchemaResultCacheMetaExtension implements ResultCacheMetaExtension
{
    public function getKey(): string
    {
        return 'phpstan-dba-schema-hash';
    }

    public function getHash(): string
    {
        try {
            $queryReflection = new QueryReflection();
            $schemaHasher = $queryReflection->getSchemaHasher();
            return $schemaHasher->hashDb();
        } catch (DbaException $e) {
            // don't break the whole process when reflector is not
            // registered properly. such error should only surface
            // when sql queries in to be analyzed code triggers the analysis
            return 'reflector-not-initialized';
        }
    }
}
