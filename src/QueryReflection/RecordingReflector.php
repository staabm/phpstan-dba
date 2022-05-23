<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

interface RecordingReflector
{
    /**
     * @return \mysqli|\PDO
     */
    public function getDatasource();
}
