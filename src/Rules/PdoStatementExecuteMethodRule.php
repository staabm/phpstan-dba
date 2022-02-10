<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Rules;

use PDOStatement;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\MixedType;
use staabm\PHPStanDba\PdoReflection\PdoStatementReflection;
use staabm\PHPStanDba\QueryReflection\PlaceholderValidation;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\UnresolvableQueryException;

/**
 * @implements Rule<MethodCall>
 *
 * @see PdoStatementExecuteErrorMethodRuleTest
 */
final class PdoStatementExecuteMethodRule implements Rule
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

        if ('execute' !== strtolower($methodReflection->getName())) {
            return [];
        }

        return $this->checkErrors($methodReflection, $methodCall, $scope);
    }

    /**
     * @return RuleError[]
     */
    private function checkErrors(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): array
    {
        $queryReflection = new QueryReflection();
        $stmtReflection = new PdoStatementReflection();
        $queryExpr = $stmtReflection->findPrepareQueryStringExpression($methodCall);

        if (null === $queryExpr) {
            return [];
        }
        if ($scope->getType($queryExpr) instanceof MixedType) {
            return [];
        }

        $args = $methodCall->getArgs();
        if (\count($args) < 1) {
            $parameterKeys = [];
            $parameterValues = [];

            $calls = $stmtReflection->findPrepareBindCalls($methodCall);

            foreach ($calls as $bindCall) {
                $args = $bindCall->getArgs();
                if (\count($args) >= 2) {
                    $keyType = $scope->getType($args[0]->value);
                    if ($keyType instanceof ConstantIntegerType || $keyType instanceof ConstantStringType) {
                        $parameterKeys[] = $keyType;
                        $parameterValues[] = $scope->getType($args[1]->value);
                    }
                }
            }

            $parameterTypes = new ConstantArrayType($parameterKeys, $parameterValues);
        } else {
            $parameterTypes = $scope->getType($args[0]->value);
        }

        try {
            $parameters = $queryReflection->resolveParameters($parameterTypes) ?? [];
        } catch (UnresolvableQueryException $exception) {
            return [
                RuleErrorBuilder::message($exception->asRuleMessage())->tip(UnresolvableQueryException::RULE_TIP)->line($methodCall->getLine())->build(),
            ];
        }

        try {
            $errors = [];
            $placeholderValidation = new PlaceholderValidation();
            foreach ($placeholderValidation->checkQuery($queryExpr, $scope, $parameters) as $error) {
                // make error messages unique
                $errors[$error] = $error;
            }
        } catch (UnresolvableQueryException $exception) {
            return [
                RuleErrorBuilder::message($exception->asRuleMessage())->tip(UnresolvableQueryException::RULE_TIP)->line($methodCall->getLine())->build(),
            ];
        }

        $ruleErrors = [];
        foreach ($errors as $error) {
            $ruleErrors[] = RuleErrorBuilder::message($error)->line($methodCall->getLine())->build();
        }

        return $ruleErrors;
    }
}
