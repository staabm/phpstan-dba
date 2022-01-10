<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Extensions;

use mysqli;
use mysqli_result;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;

final class MysqliQueryDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension, DynamicFunctionReturnTypeExtension
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
        return mysqli::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return 'query' === $methodReflection->getName();
    }

    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return 'mysqli_query' === $functionReflection->getName();
    }

    public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
    {
        $args = $functionCall->getArgs();
        $defaultReturn = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();

        if (QueryReflection::getRuntimeConfiguration()->throwsMysqliExceptions($this->phpVersion)) {
            $defaultReturn = TypeCombinator::remove($defaultReturn, new ConstantBooleanType(false));
        }

        if (\count($args) < 2) {
            return $defaultReturn;
        }

		$resultType = $this->inferResultType($args[1]->value, $scope);
		if ($resultType !== null) {
			return $resultType;
		}

		return $defaultReturn;
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
	{
		$args = $methodCall->args;
		$defaultReturn = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

		if (QueryReflection::getRuntimeConfiguration()->throwsMysqliExceptions($this->phpVersion)) {
			$defaultReturn = TypeCombinator::remove($defaultReturn, new ConstantBooleanType(false));
		}

		if (\count($args) < 1) {
			return $defaultReturn;
		}

		$resultType = $this->inferResultType($args[0]->value, $scope);
		if ($resultType !== null) {
			return $resultType;
		}

        return $defaultReturn;
    }

	private function inferResultType(Expr $queryExpr, Scope $scope): ?Type {
		$queryReflection = new QueryReflection();
		$queryString = $queryReflection->resolveQueryString($queryExpr, $scope);
		if (null === $queryString) {
			return null;
		}

		$resultType = $queryReflection->getResultType($queryString, QueryReflector::FETCH_TYPE_ASSOC);
		if ($resultType) {
			if (QueryReflection::getRuntimeConfiguration()->throwsMysqliExceptions($this->phpVersion)) {
				return new GenericObjectType(mysqli_result::class, [$resultType]);
			}

			return TypeCombinator::union(
				new GenericObjectType(mysqli_result::class, [$resultType]),
				new ConstantBooleanType(false),
			);
		}

		return null;
	}
}
