<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PDOStatement;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MethodTypeSpecifyingExtension;
use PHPStan\Type\Type;
use staabm\PHPStanDba\PdoReflection\PdoStatementReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;

final class PdoExecuteTypeSpecifyingExtension implements MethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{
    private TypeSpecifier $typeSpecifier;

    public function getClass(): string
    {
        return PDOStatement::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection, MethodCall $node, TypeSpecifierContext $context): bool
    {
        return 'execute' === $methodReflection->getName();
    }

    public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
    {
        $this->typeSpecifier = $typeSpecifier;
    }

    public function specifyTypes(MethodReflection $methodReflection, MethodCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        // keep original param name because named-parameters
        $methodCall = $node;
        $stmtType = $scope->getType($methodCall->var);

        $inferedType = $this->inferStatementType($methodCall, $scope);
        if (null !== $inferedType) {
            return $this->typeSpecifier->create($methodCall->var, $inferedType, TypeSpecifierContext::createTruthy(), true);
        }

        return $this->typeSpecifier->create($methodCall->var, $stmtType, TypeSpecifierContext::createTruthy());
    }

    private function inferStatementType(MethodCall $methodCall, Scope $scope): ?Type
    {
        $args = $methodCall->getArgs();

        if (0 === \count($args)) {
            return null;
        }

        $stmtReflection = new PdoStatementReflection();
        $queryExpr = $stmtReflection->findQueryStringExpression($methodCall);
        if (null === $queryExpr) {
            return null;
        }

        // resolve query parameter from "prepare"
        if ($queryExpr instanceof MethodCall) {
            $queryArgs = $queryExpr->getArgs();
            $queryExpr = $queryArgs[0]->value;
        }

        $parameterTypes = $scope->getType($args[0]->value);

        $queryReflection = new QueryReflection();
        $queryString = $queryReflection->resolvePreparedQueryString($queryExpr, $parameterTypes, $scope);
        if (null === $queryString) {
            return null;
        }

        $reflectionFetchType = QueryReflector::FETCH_TYPE_BOTH;
        $resultType = $queryReflection->getResultType($queryString, $reflectionFetchType);

        if ($resultType) {
            return new GenericObjectType(PDOStatement::class, [$resultType]);
        }

        return null;
    }
}
