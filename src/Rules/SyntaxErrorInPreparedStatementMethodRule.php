<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use staabm\PHPStanDba\QueryReflection\QueryReflection;

/**
 * @implements Rule<MethodCall>
 *
 * @see SyntaxErrorInPreparedStatementMethodRuleTest
 */
final class SyntaxErrorInPreparedStatementMethodRule implements Rule
{
    /**
     * @var list<string>
     */
    private $classMethods;

    /**
     * @param list<string> $classMethods
     */
    public function __construct(array $classMethods)
    {
        $this->classMethods = $classMethods;
    }

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

        $unsupportedMethod = true;
        foreach ($this->classMethods as $classMethod) {
            sscanf($classMethod, '%[^::]::%s', $className, $methodName);

            if ($methodName === $methodReflection->getName() && $className === $methodReflection->getDeclaringClass()->getName()) {
                $unsupportedMethod = false;
                break;
            }
        }

        if ($unsupportedMethod) {
            return [];
        }

        return $this->checkErrors($methodCall, $scope);
    }

    /**
     * @return RuleError[]
     */
    private function checkErrors(MethodCall $methodCall, Scope $scope): array
    {
        $args = $methodCall->getArgs();

        if (\count($args) < 2) {
            return [];
        }

        $queryExpr = $args[0]->value;
        $parameterTypes = $scope->getType($args[1]->value);

        $queryReflection = new QueryReflection();
        $queryString = $queryReflection->resolvePreparedQueryString($queryExpr, $parameterTypes, $scope);
        if (null === $queryString) {
            return [];
        }

        $error = $queryReflection->validateQueryString($queryString);
        if (null !== $error) {
            return [
                RuleErrorBuilder::message('Query error: '.$error->getMessage().' ('.$error->getCode().').')->line($methodCall->getLine())->build(),
            ];
        }

        return [];
    }
}
