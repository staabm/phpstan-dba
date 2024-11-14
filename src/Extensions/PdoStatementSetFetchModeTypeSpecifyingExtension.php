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
use PHPStan\Type\MethodTypeSpecifyingExtension;
use staabm\PHPStanDba\PdoReflection\PdoStatementObjectType;
use staabm\PHPStanDba\PdoReflection\PdoStatementReflection;

final class PdoStatementSetFetchModeTypeSpecifyingExtension implements MethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{
    private TypeSpecifier $typeSpecifier;

    public function getClass(): string
    {
        return PDOStatement::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection, MethodCall $node, TypeSpecifierContext $context): bool
    {
        return 'setfetchmode' === strtolower($methodReflection->getName());
    }

    public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
    {
        $this->typeSpecifier = $typeSpecifier;
    }

    public function specifyTypes(MethodReflection $methodReflection, MethodCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        // keep original param name because named-parameters
        $methodCall = $node;
        $statementType = $scope->getType($methodCall->var);

        if ($statementType instanceof PdoStatementObjectType) {
            $reducedType = $this->reduceType($methodCall, $statementType, $scope);

            if (null !== $reducedType) {
                return $this->typeSpecifier->create($methodCall->var, $reducedType, TypeSpecifierContext::createTruthy(), $scope)->setAlwaysOverwriteTypes();
            }
        }

        return new SpecifiedTypes();
    }

    private function reduceType(MethodCall $methodCall, PdoStatementObjectType $statementType, Scope $scope): ?PdoStatementObjectType
    {
        $args = $methodCall->getArgs();

        if (\count($args) < 1) {
            return null;
        }

        $pdoStatementReflection = new PdoStatementReflection();

        $fetchModeType = $scope->getType($args[0]->value);
        $fetchType = $pdoStatementReflection->getFetchType($fetchModeType);
        if (null === $fetchType) {
            return null;
        }

        return $statementType->newWithFetchType($fetchType);
    }
}
