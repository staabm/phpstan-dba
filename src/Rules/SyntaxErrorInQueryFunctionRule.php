<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use staabm\PHPStanDba\QueryReflection\QueryReflection;

/**
 * @implements Rule<FuncCall>
 */
final class SyntaxErrorInQueryFunctionRule implements Rule
{
    private ReflectionProvider $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Node\Name) {
            return [];
        }

        $functionName = $this->reflectionProvider->resolveFunctionName($node->name, $scope);
        if (null === $functionName) {
            return [];
        }

        if ('deployer\runmysqlquery' !== strtolower($functionName)) {
            return [];
        }

        $args = $node->getArgs();
        $errors = [];

        $queryReflection = new QueryReflection();
        if ($queryReflection->containsSyntaxError($args[0]->value, $scope)) {
            $errors[] = RuleErrorBuilder::message('Query contains a syntax error.')->line($node->getLine())->build();
        }

        return $errors;
    }
}
