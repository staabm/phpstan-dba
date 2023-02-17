<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PDO;

/**
 * This class was kept for BC reasons.
 *
 * @deprecated use PdoMysqlQueryReflector instead
 */
final class PdoQueryReflector extends PdoMysqlQueryReflector // @phpstan-ignore-line
{
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }
}
