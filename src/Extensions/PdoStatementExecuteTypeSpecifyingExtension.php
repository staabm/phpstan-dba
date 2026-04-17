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
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\MethodTypeSpecifyingExtension;
use PHPStan\Type\Type;
use staabm\PHPStanDba\PdoReflection\PdoStatementReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\UnresolvableQueryException;

final class PdoStatementExecuteTypeSpecifyingExtension implements MethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{
    private TypeSpecifier $typeSpecifier;

    public function getClass(): string
    {
        return PDOStatement::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection, MethodCall $node, TypeSpecifierContext $context): bool
    {
        return 'execute' === strtolower($methodReflection->getName());
    }

    public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
    {
        $this->typeSpecifier = $typeSpecifier;
    }

    public function specifyTypes(MethodReflection $methodReflection, MethodCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        // keep original param name because named-parameters
        $methodCall = $node;

        try {
            $inferredType = $this->inferStatementType($methodReflection, $methodCall, $scope);
        } catch (UnresolvableQueryException $e) {
            return new SpecifiedTypes();
        }

        if (null !== $inferredType) {
            return $this->typeSpecifier->create($methodCall->var, $inferredType, TypeSpecifierContext::createTruthy(), $scope)->setAlwaysOverwriteTypes();
        }

        return new SpecifiedTypes();
    }

    /**
     * @throws UnresolvableQueryException
     */
    private function inferStatementType(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
    {
        $args = $methodCall->getArgs();



        $stmtReflection = new PdoStatementReflection();
        $queryExpr = $stmtReflection->findPrepareQueryStringExpression($methodCall);
        if (null === $queryExpr) {
            return null;
        }

        $queryReflection = new QueryReflection();

        if (0 === \count($args)) {
            $parameterKeys = [];
            $parameterValues = [];

            foreach ($stmtReflection->findPrepareBindCalls($methodCall) as $bindCall) {
                $bindArgs = $bindCall->getArgs();
                if (\count($bindArgs) >= 2) {
                    $keyType = $scope->getType($bindArgs[0]->value);
                    if ($keyType instanceof ConstantIntegerType || $keyType instanceof ConstantStringType) {
                        $parameterKeys[] = $keyType;
                        $parameterValues[] = $scope->getType($bindArgs[1]->value);
                    }
                }
            }

            $parameterTypes = new ConstantArrayType($parameterKeys, $parameterValues);
        } else {
            $parameterTypes = $queryReflection->resolveParameterTypes($args[0]->value, $scope);
        }

        $queryStrings = $queryReflection->resolvePreparedQueryStrings($queryExpr, $parameterTypes, $scope);

        $reflectionFetchType = QueryReflection::getRuntimeConfiguration()->getDefaultFetchMode();

        return $stmtReflection->createGenericStatement($queryStrings, $reflectionFetchType);
    }
}
