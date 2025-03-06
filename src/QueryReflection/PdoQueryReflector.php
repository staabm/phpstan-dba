<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PDO;

/**
 * This class was kept for BC reasons.
 *
 * @deprecated use PdoMysqlQueryReflector instead
 * @phpstan-ignore class.extendsFinalByPhpDoc
 */
final class PdoQueryReflector extends PdoMysqlQueryReflector
{
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }
}
