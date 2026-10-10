<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\ResultCache\ResultCacheValueExtension;
use PHPStan\Analyser\Scope;
use staabm\PHPStanDba\DbaException;
use staabm\PHPStanDba\QueryReflection\QueryReflection;

final class DbSchemaResultCacheMetaExtension implements ResultCacheValueExtension
{
    public const SCHEMA_KEY = 'schema';

    /**
     * Marks the currently analysed file as dependent on the database schema.
     */
    public static function trackSchemaDependency(Scope $scope): void
    {
        if ($scope instanceof DependencyTracker) {
            $scope->trackValueDependency(self::class, self::SCHEMA_KEY);
        }
    }

    public function getValue(string $key): string
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

    public function keyToResultCache(string $key): string
    {
        return $key;
    }

    public function keyFromResultCache(string $storedKey): string
    {
        return $storedKey;
    }
}
