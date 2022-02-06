<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use PDO;
use PDOStatement;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MethodTypeSpecifyingExtension;
use PHPStan\Type\Type;
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

        $reducedType = $this->reduceType($methodCall, $statementType, $scope);
        if (null !== $reducedType) {
            $statementType = new GenericObjectType(PDOStatement::class, [$reducedType]);

            return $this->typeSpecifier->create($methodCall->var, $statementType, TypeSpecifierContext::createTruthy(), true);
        }

        return $this->typeSpecifier->create($methodCall->var, $statementType, TypeSpecifierContext::createTruthy());
    }

    private function reduceType(MethodCall $methodCall, Type $statementType, Scope $scope): ?Type
    {
        $args = $methodCall->getArgs();

        if (\count($args) < 1) {
            return null;
        }

        $fetchModeType = $scope->getType($args[0]->value);
        if (!$fetchModeType instanceof ConstantIntegerType) {
            return null;
        }
        $fetchType = $fetchModeType->getValue();

        if (!\in_array($fetchType, [PDO::FETCH_ASSOC, PDO::FETCH_NUM, PDO::FETCH_BOTH])) {
            return null;
        }

        $pdoStatementReflection = new PdoStatementReflection();

        return $pdoStatementReflection->getStatementResultType($statementType, $fetchType);
    }
}
