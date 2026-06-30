<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PHPStan\Analyser\ResultCache\ResultCacheMetaExtension;
use staabm\PHPStanDba\QueryReflection\QueryReflection;

final class DbSchemaResultCacheMetaExtension implements ResultCacheMetaExtension {

    public function getKey(): string
    {
        return 'phpstan-dba-schema-hash';
    }

    public function getHash(): string
    {
        $queryReflection = new QueryReflection();
        $schemaHasher = $queryReflection->getSchemaHasher();

        if ($schemaHasher === null) {
            // a reflector is used which we don't have a schema hash implementation for.
            // return a hardcoded string which reflects the situation we had before implementing ResultCacheMetaExtension.
            // => result cache will not automatically invalidate on schema changes
            return 'unknown';
        }
        return $schemaHasher->hashDb();
    }
}
