<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\Tests\SyntaxErrorInQueryFunctionRuleTest;
use staabm\PHPStanDba\UnresolvableQueryException;

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
        if (! $node->name instanceof Node\Name) {
            return [];
        }

        $calledFunctionName = $this->reflectionProvider->resolveFunctionName($node->name, $scope);
        if (null === $calledFunctionName) {
            return [];
        }

        $queryArgPosition = null;
        $unsupportedFunction = true;
        foreach ($this->functionNames as $functionName) {
            sscanf($functionName, '%[^#]#%i', $functionName, $queryArgPosition);
            if (! \is_string($functionName) || ! \is_int($queryArgPosition)) {
                throw new ShouldNotHappenException('Invalid functionName definition');
            }

            if (strtolower($functionName) === strtolower($calledFunctionName)) {
                $unsupportedFunction = false;
                break;
            }
        }

        if (null === $queryArgPosition) {
            throw new ShouldNotHappenException('Invalid classMethod definition');
        }
        if ($unsupportedFunction) {
            return [];
        }

        $args = $node->getArgs();

        if (! \array_key_exists($queryArgPosition, $args)) {
            return [];
        }

        $queryExpr = $args[$queryArgPosition]->value;
        $queryReflection = new QueryReflection();

        if ($queryReflection->isResolvable($queryExpr, $scope)->no()) {
            return [];
        }

        try {
            foreach ($queryReflection->resolveQueryStrings($queryExpr, $scope) as $queryString) {
                $queryError = $queryReflection->validateQueryString($queryString);
                if (null !== $queryError) {
                    return [
                        RuleErrorBuilder::message($queryError->asRuleMessage())->line($node->getStartLine())->build(),
                    ];
                }
            }
        } catch (UnresolvableQueryException $exception) {
            return [
                RuleErrorBuilder::message($exception->asRuleMessage())->tip($exception::getTip())->line($node->getStartLine())->build(),
            ];
        }

        return [];
    }
}
