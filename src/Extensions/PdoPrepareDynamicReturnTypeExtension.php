<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PDO;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use staabm\PHPStanDba\PdoReflection\PdoStatementReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\UnresolvableQueryException;

final class PdoPrepareDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * @var PhpVersion
     */
    private $phpVersion;

    public function __construct(PhpVersion $phpVersion)
    {
        $this->phpVersion = $phpVersion;
    }

    public function getClass(): string
    {
        return PDO::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return 'prepare' === $methodReflection->getName();
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $args = $methodCall->getArgs();
        $defaultReturn = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $methodReflection->getVariants()
        )->getReturnType();

        if (QueryReflection::getRuntimeConfiguration()->throwsPdoExceptions($this->phpVersion)) {
            $defaultReturn = TypeCombinator::remove($defaultReturn, new ConstantBooleanType(false));
        }

        if (\count($args) < 1) {
            return $defaultReturn;
        }

        if ($scope->getType($args[0]->value) instanceof MixedType) {
            return $defaultReturn;
        }

        try {
            $resultType = $this->inferType($args[0]->value, $scope);
            if (null !== $resultType) {
                return $resultType;
            }
        } catch (UnresolvableQueryException $exception) {
            // simulation not possible.. use default value
        }

        return $defaultReturn;
    }

    private function inferType(Expr $queryExpr, Scope $scope): ?Type
    {
        $queryReflection = new QueryReflection();
        $queryStrings = $queryReflection->resolveQueryStrings($queryExpr, $scope);

        $reflectionFetchType = QueryReflection::getRuntimeConfiguration()->getDefaultFetchMode();
        $pdoStatementReflection = new PdoStatementReflection();

        return $pdoStatementReflection->createGenericStatement($queryStrings, $reflectionFetchType);
    }
}
