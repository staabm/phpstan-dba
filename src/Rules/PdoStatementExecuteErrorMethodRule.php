<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Rules;

use PDOStatement;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use staabm\PHPStanDba\PdoReflection\PdoStatementReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflector;

/**
 * @implements Rule<MethodCall>
 *
 * @see PdoStatementExecuteErrorMethodRuleTest
 */
final class PdoStatementExecuteErrorMethodRule implements Rule
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $methodCall, Scope $scope): array
    {
        if (!$methodCall->name instanceof Node\Identifier) {
            return [];
        }

        $methodReflection = $scope->getMethodReflection($scope->getType($methodCall->var), $methodCall->name->toString());
        if (null === $methodReflection) {
            return [];
        }

        if (PdoStatement::class !== $methodReflection->getDeclaringClass()->getName()) {
            return [];
        }

        if ('execute' !== $methodReflection->getName()) {
            return [];
        }

        return $this->checkErrors($methodReflection, $methodCall, $scope);
    }

    /**
     * @return RuleError[]
     */
    private function checkErrors(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): array
    {
        $stmtReflection = new PdoStatementReflection();
        $queryExpr = $stmtReflection->findPrepareQueryStringExpression($methodReflection, $methodCall);

        if (null === $queryExpr) {
            return [];
        }

        $queryReflection = new QueryReflection();
        $queryString = $queryReflection->resolveQueryString($queryExpr, $scope);
        if (null === $queryString) {
            return [];
        }

        $args = $methodCall->getArgs();
        $placeholderCount = $this->countPlaceholders($queryString);

        if (0 === \count($args)) {
            if (0 === $placeholderCount) {
                return [];
            }

            return [
                RuleErrorBuilder::message(sprintf('Query expects %s placeholders, but no values are given to execute().', $placeholderCount))->line($methodCall->getLine())->build(),
            ];
        }

        return $this->checkParameterValues($methodCall, $scope, $queryExpr, $queryString, $placeholderCount);
    }

    /**
     * @return RuleError[]
     */
    private function checkParameterValues(MethodCall $methodCall, Scope $scope, Expr $queryExpr, string $queryString, int $placeholderCount): array
    {
        $queryReflection = new QueryReflection();
        $args = $methodCall->getArgs();

        $parameterTypes = $scope->getType($args[0]->value);
        $parameters = $queryReflection->resolveParameters($parameterTypes);
        if (null === $parameters) {
            return [];
        }
		if (!$parameterTypes instanceof ConstantArrayType) {
			throw new ShouldNotHappenException();
		}

        $parameterCount = \count($parameters);
        if ($parameterCount !== $placeholderCount) {
            if (1 === $parameterCount) {
                return [
                    RuleErrorBuilder::message(sprintf('Query expects %s placeholders, but %s value is given to execute().', $placeholderCount, $parameterCount))->line($methodCall->getLine())->build(),
                ];
            }

            return [
                RuleErrorBuilder::message(sprintf('Query expects %s placeholders, but %s values are given to execute().', $placeholderCount, $parameterCount))->line($methodCall->getLine())->build(),
            ];
        }

        $errors = [];
        $namedPlaceholders = $this->extractNamedPlaceholders($queryString);
        foreach ($namedPlaceholders as $namedPlaceholder) {
            if (!\array_key_exists($namedPlaceholder, $parameters)) {
                $errors[] = RuleErrorBuilder::message(sprintf('Query expects placeholder %s, but it is missing from values given to execute().', $namedPlaceholder))->line($methodCall->getLine())->build();
            }
        }

        foreach ($parameters as $placeholderKey => $value) {
            if (!\in_array($placeholderKey, $namedPlaceholders)) {
                $errors[] = RuleErrorBuilder::message(sprintf('Value %s is given to execute(), but the query does not containt this placeholder.', $placeholderKey))->line($methodCall->getLine())->build();
            }
        }

		if ($errors != []) {
			return $errors;
		}

		return $this->checkParameterTypes($methodCall, $queryExpr, $parameterTypes, $scope);
    }

	/**
	 * @return RuleError[]
	 */
	private function checkParameterTypes(MethodCall $methodCall, Expr $queryExpr, ConstantArrayType $parameterTypes, Scope $scope): array
	{
		$queryReflection = new QueryReflection();
		$queryString = $queryReflection->resolvePreparedQueryString($queryExpr, $parameterTypes, $scope);
		if (null === $queryString) {
			return [];
		}

		$resultType = $queryReflection->getResultType($queryString, QueryReflector::FETCH_TYPE_ASSOC);
		if (!$resultType instanceof ConstantArrayType) {
			return [];
		}

		var_dump($queryString);
		foreach ($resultType->getKeyTypes() as $keyType) {
			var_dump($keyType->describe(VerbosityLevel::value()));
		}

		$errors = [];
		$keyTypes = $parameterTypes->getKeyTypes();
		$valueTypes = $parameterTypes->getValueTypes();

		foreach ($keyTypes as $i => $keyType) {
			if (!$keyType instanceof ConstantStringType) {
				continue;
			}

			$columnName = $keyType->getValue();
			ltrim($columnName, ':');

			var_dump($keyType->describe(VerbosityLevel::precise()));
			var_dump($resultType->hasOffsetValueType($keyType)->describe());
			if (!$resultType->hasOffsetValueType($keyType)->yes()) {
				// we only know types of columns which are selected.
				continue;
			}

			var_dump($resultType->getOffsetValueType($keyType)->describe(VerbosityLevel::precise()));
			var_dump($valueTypes[$i]->describe(VerbosityLevel::precise()));
			if ($resultType->getOffsetValueType($keyType)->isSuperTypeOf($valueTypes[$i])->yes()) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(
					sprintf(
						'Value %s with type %s is given to execute(), but the query expects %s.',
						$placeholderName,
						$resultType->getOffsetValueType($keyType)->describe(VerbosityLevel::precise()),
						$valueTypes[$i]->describe(VerbosityLevel::precise())
					)
				)->line($methodCall->getLine())->build();
		}

		return $errors;
	}

    /**
     * @return 0|positive-int
     */
    private function countPlaceholders(string $queryString): int
    {
        $numPlaceholders = substr_count($queryString, '?');

        if (0 !== $numPlaceholders) {
            return $numPlaceholders;
        }

        $numPlaceholders = preg_match_all('{:[a-z]+}', $queryString);
        if (false === $numPlaceholders || $numPlaceholders < 0) {
            throw new ShouldNotHappenException();
        }

        return $numPlaceholders;
    }

    /**
     * @return list<string>
     */
    private function extractNamedPlaceholders(string $queryString): array
    {
        // pdo does not support mixing of named and '?' placeholders
        $numPlaceholders = substr_count($queryString, '?');

        if (0 !== $numPlaceholders) {
            return [];
        }

        if (preg_match_all('{:[a-z]+}', $queryString, $matches) > 0) {
            return $matches[0];
        }

        return [];
    }
}
