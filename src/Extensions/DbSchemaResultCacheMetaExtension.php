<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PHPStan\Analyser\ResultCache\ResultCacheMetaExtension;
use staabm\PHPStanDba\QueryReflection\QueryReflection;

final class DbSchemaResultCacheMetaExtension implements ResultCacheMetaExtension
{
    public function getKey(): string
    {
        return 'phpstan-dba-schema-hash';
    }

    public function getHash(): string
    {
        $queryReflection = new QueryReflection();
        $schemaHasher = $queryReflection->getSchemaHasher();
        return $schemaHasher->hashDb();
    }
}
