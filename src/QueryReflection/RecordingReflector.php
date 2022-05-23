<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

interface RecordingReflector
{
    /**
     * Returns the underlying datasource of a reflector.
     *
     * Beware this might establish a database connection, in case a reflector is implemented lazily.
     * Therefore calling this method might have a negative performance impact.
     *
     * @return \mysqli|\PDO|null
     */
    public function getDatasource();
}
