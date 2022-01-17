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
use staabm\PHPStanDba\Tests\SyntaxErrorInQueryFunctionRuleTest;

/**
 * @implements Rule<FuncCall>
 *
 * @see SyntaxErrorInQueryFunctionRuleTest
 */
final class SyntaxErrorInQueryFunctionRule implements Rule
{
    /**
     * @var list<string>
     */
    private $functionNames;

    /**
     * @var ReflectionProvider
     */
    private $reflectionProvider;

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
        $queryArgPosition = null;
        foreach ($this->functionNames as $functionName) {
            sscanf($functionName, '%[^#]#%s', $functionName, $queryArgPosition);

            if (strtolower($functionName) === strtolower($calledFunctionName)) {
                $unsupportedFunction = false;
                break;
            }
        }

        if ($unsupportedFunction) {
            return [];
        }

        $args = $node->getArgs();

        if (!\array_key_exists($queryArgPosition, $args)) {
            return [];
        }

        $queryReflection = new QueryReflection();
        $queryString = $queryReflection->resolveQueryString($args[$queryArgPosition]->value, $scope);
        if (null === $queryString) {
            return [];
        }

        $error = $queryReflection->validateQueryString($queryString);
        if (null !== $error) {
            return [
                RuleErrorBuilder::message('Query error: '.$error->getMessage().' ('.$error->getCode().').')->line($node->getLine())->build(),
            ];
        }

        return [];
    }
}
