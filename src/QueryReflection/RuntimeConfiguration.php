<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\Php\PhpVersion;

final class RuntimeConfiguration
{
    public const ERROR_MODE_EXCEPTION = 'exception';
    public const ERROR_MODE_DEFAULT = 'default';

    /**
     * @var self::ERROR_MODE*
     */
    private $errorMode = self::ERROR_MODE_DEFAULT;

    public static function create():self
    {
        return new self();
    }

    /**
     * @param self::ERROR_MODE* $mode
     */
    public function errorMode($mode):self
    {
        $this->errorMode = $mode;

		return $this;
    }

    public function throwsPdoExceptions(PhpVersion $phpVersion): bool
    {
        if (self::ERROR_MODE_EXCEPTION === $this->errorMode) {
            return true;
        }

        // since php8 the pdo php-src default error mode changed to exception
        return $phpVersion->getVersionId() >= 80000;
    }

    public function throwsMysqliExceptions(PhpVersion $phpVersion): bool
    {
        if (self::ERROR_MODE_EXCEPTION === $this->errorMode) {
            return true;
        }

        // since php8.1 the mysqli php-src default error mode changed to exception
        return $phpVersion->getVersionId() >= 80100;
    }
}
