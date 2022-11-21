<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\TypeMapping;

use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Type;
use staabm\PHPStanDba\QueryReflection\DbaApi;

final class MysqliTypeMapper
{
    /** @var array<int, string> */
    private $nativeTypes = [];

    /** @var array<int, string> */
    private $nativeFlags = [];

    /**
     * @var MysqlTypeMapper
     */
    private $typeMapper;

    public function __construct(?DbaApi $dbaApi)
    {
        $constants = get_defined_constants(true);
        foreach ($constants['mysqli'] as $c => $n) {
            if (!\is_int($n)) {
                // skip bool constants like MYSQLI_IS_MARIADB
                continue;
            }
            if (preg_match('/^MYSQLI_TYPE_(.*)/', $c, $m)) {
                if (!\is_string($m[1])) {
                    throw new ShouldNotHappenException();
                }
                $this->nativeTypes[$n] = $m[1];
            } elseif (preg_match('/MYSQLI_(.*)_FLAG$/', $c, $m)) {
                if (!\is_string($m[1])) {
                    throw new ShouldNotHappenException();
                }
                if (!\array_key_exists($n, $this->nativeFlags)) {
                    $this->nativeFlags[$n] = $m[1];
                }
            }
        }

        $this->typeMapper = new MysqlTypeMapper($dbaApi);
    }

    public function mapToPHPStanType(int $mysqlType, int $mysqlFlags, int $length): Type
    {
        return $this->typeMapper->mapToPHPStanType($this->type2txt($mysqlType), $this->flags2txt($mysqlFlags), $length);
    }

    private function type2txt(int $typeId): string
    {
        return \array_key_exists($typeId, $this->nativeTypes) ? $this->nativeTypes[$typeId] : '';
    }

    /** @return list<string> */
    private function flags2txt(int $flagId): array
    {
        $result = [];
        foreach ($this->nativeFlags as $n => $t) {
            if ($flagId & $n) {
                $result[] = $t;
            }
        }

        return $result;
    }
}
