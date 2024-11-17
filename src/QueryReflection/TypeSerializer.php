<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\PhpDocParser\Printer\Printer;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Type;
use staabm\PHPStanDba\Error;

final class TypeSerializer
{
    private ?Printer $printer = null;

    private ?TypeStringResolver $typeStringResolver = null;

    /**
     * @param array<string, array{error?: ?Error, result?: array<QueryReflector::FETCH_TYPE*, ?Type>}> $records
     * @return array<string, array{error?: ?Error, result?: array<QueryReflector::FETCH_TYPE*, ?array<string>>}>
     */
    public function serialize(array $records): array
    {
        // serialize types, see https://github.com/phpstan/phpstan/discussions/12046
        foreach ($records as &$record) {
            if (! array_key_exists('result', $record)) {
                continue;
            }
            $record['result'] = array_map(function (?Type $type) {
                if ($type === null) {
                    return null;
                }

                return [
                    'type-description' => $this->getPhpdocPrinter()->print($type->toPhpDocNode()),
                ];
            }, $record['result']);
        }

        return $records; // @phpstan-ignore return.type
    }

    /**
     * @param array<string, array{error?: ?Error, result?: array<QueryReflector::FETCH_TYPE*, ?array<string>>}> $records
     * @return array<string, array{error?: ?Error, result?: array<QueryReflector::FETCH_TYPE*, ?Type>}>
     */
    public function unserialize(array $records): array
    {
        // serialize types, see https://github.com/phpstan/phpstan/discussions/12046
        foreach ($records as &$record) {
            if (! array_key_exists('result', $record)) {
                continue;
            }
            $record['result'] = array_map(function ($serialized): Type {
                if (is_array($serialized) && array_key_exists('type-description', $serialized)) {
                    try {
                        return $this->getTypeStringResolver()->resolve($serialized['type-description']);
                    } catch (\Throwable $e) {
                        throw new ShouldNotHappenException("unexpected type " . print_r($serialized, true) . ': ' . $e->getMessage());
                    }
                }
                throw new ShouldNotHappenException("unexpected type " . print_r($serialized, true));
            }, $record['result']);
        }

        return $records; // @phpstan-ignore return.type
    }

    private function getPhpdocPrinter(): Printer
    {
        if ($this->printer === null) {
            $this->printer = DIContainerBridge::getByType(Printer::class);
        }
        return $this->printer;
    }

    private function getTypeStringResolver(): TypeStringResolver
    {
        if ($this->typeStringResolver === null) {
            $this->typeStringResolver = DIContainerBridge::getByType(TypeStringResolver::class);
        }
        return $this->typeStringResolver;
    }
}
