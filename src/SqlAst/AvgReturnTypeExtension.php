<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\SqlAst;

use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\FunctionCall;

final class AvgReturnTypeExtension implements QueryFunctionReturnTypeExtension
{
    /**
     * @var bool
     */
    private $hasGroupBy;

    public function __construct(bool $hasGroupBy)
    {
        $this->hasGroupBy = $hasGroupBy;
    }

    public function isFunctionSupported(FunctionCall $expression): bool
    {
        return \in_array($expression->getFunction()->getName(), [BuiltInFunction::AVG], true);
    }

    public function getReturnType(FunctionCall $expression, QueryScope $scope): ?Type
    {
        $args = $expression->getArguments();

        if (1 !== \count($args)) {
            return null;
        }

        $argType = $scope->getType($args[0]);

        if ($argType->isNull()->yes()) {
            return $argType;
        }
        $containsNull = TypeCombinator::containsNull($argType);
        $argType = TypeCombinator::removeNull($argType);

        if ($argType instanceof UnionType) {
            $newTypes = [];
            foreach ($argType->getTypes() as $type) {
                $newTypes[] = $this->convertToAvgType($type);
            }
            $newType = TypeCombinator::union(...$newTypes);
        } else {
            $newType = $this->convertToAvgType($argType);
        }

        if ($containsNull || ! $this->hasGroupBy) {
            $newType = TypeCombinator::addNull($newType);
        }
        return $newType;
    }

    private function convertToAvgType(Type $argType): Type
    {
        if ($argType->isInteger()->yes()) {
            return new IntersectionType([
                new StringType(),
                new AccessoryNumericStringType(),
            ]);
        }

        if ($argType->isString()->yes()) {
            return $argType->toFloat();
        }

        return $argType;
    }
}
