<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\MixedType;
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

        if ($scope->getType($args[$queryArgPosition]->value) instanceof MixedType) {
            return [];
        }

        $queryReflection = new QueryReflection();
        try {
            foreach ($queryReflection->resolveQueryStrings($args[$queryArgPosition]->value, $scope) as $queryString) {
                $queryError = $queryReflection->validateQueryString($queryString);
                if (null !== $queryError) {
                    return [
                        RuleErrorBuilder::message($queryError->asRuleMessage())->line($node->getLine())->build(),
                    ];
                }
            }
        } catch (UnresolvableQueryException $exception) {
            return [
                RuleErrorBuilder::message($exception->asRuleMessage())->tip(UnresolvableQueryException::RULE_TIP)->line($node->getLine())->build(),
            ];
        }

        return [];
    }
}
