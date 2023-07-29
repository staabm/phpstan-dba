<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Rules;

use PDOStatement;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use staabm\PHPStanDba\PdoReflection\PdoStatementReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\UnresolvableQueryException;

/**
 * @implements Rule<MethodCall>
 *
 * @see SyntaxErrorInPdoExecuteMethodRuleTest
 */
final class SyntaxErrorInPdoExecuteMethodRule implements Rule
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Node\Identifier) {
            return [];
        }

        $methodReflection = $scope->getMethodReflection($scope->getType($node->var), $node->name->toString());
        if (null === $methodReflection) {
            return [];
        }

        if (PDOStatement::class !== $methodReflection->getDeclaringClass()->getName()) {
            return [];
        }

        if ('execute' !== strtolower($methodReflection->getName())) {
            return [];
        }

        return $this->analyze($node, $scope);
    }

    /**
     * @return RuleError[]
     */
    private function analyze(MethodCall $node, Scope $scope): array
    {
        $args = $node->getArgs();
        if (count($args) < 1) {
            return [];
        }

        $queryReflection = new QueryReflection();
        $stmtReflection = new PdoStatementReflection();
        $queryExpr = $stmtReflection->findPrepareQueryStringExpression($node);

        if (null === $queryExpr) {
            return [];
        }
        if ($queryReflection->isResolvable($queryExpr, $scope)->no()) {
            return [];
        }

        $parameterTypes = $scope->getType($args[0]->value);

        try {
            $queryStrings = $queryReflection->resolvePreparedQueryStrings($queryExpr, $parameterTypes, $scope);

            foreach ($queryStrings as $queryString) {
                $queryError = $queryReflection->validateQueryString($queryString);
                if (null !== $queryError) {
                    return [
                        RuleErrorBuilder::message($queryError->asRuleMessage())->line($node->getLine())->build(),
                    ];
                }
            }
        } catch (UnresolvableQueryException $exception) {
            return [
                RuleErrorBuilder::message($exception->asRuleMessage())->tip($exception::getTip())->line($node->getLine())->build(),
            ];
        }

        return [];
    }
}
