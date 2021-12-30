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
    /**
     * @var list<string>
     */
    private $functionNames;

    private ReflectionProvider $reflectionProvider;

    /**
     * @param list<string> $functionNames
     */
    public function __construct(array $functionNames, ReflectionProvider $reflectionProvider)
    {
        $this->functionNames = $functionNames;
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

        $calledFunctionName = $this->reflectionProvider->resolveFunctionName($node->name, $scope);
        if (null === $calledFunctionName) {
            return [];
        }

        $unsupportedFunction = true;
        foreach ($this->functionNames as $functionName) {
            if (strtolower($functionName) === strtolower($calledFunctionName)) {
                $unsupportedFunction = false;
                break;
            }
        }

        if ($unsupportedFunction) {
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
