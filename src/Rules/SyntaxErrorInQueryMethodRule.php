<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use staabm\PHPStanDba\QueryReflection\QueryReflection;

/**
 * @implements Rule<MethodCall>
 *
 * @see SyntaxErrorInQueryMethodRuleTest
 */
final class SyntaxErrorInQueryMethodRule implements Rule
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

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Node\Identifier) {
            return [];
        }

        $methodReflection = $scope->getMethodReflection($scope->getType($node->var), $node->name->toString());
        if (null === $methodReflection) {
            return [];
        }

        $unsupportedMethod = true;
        $queryArgPosition = null;
        foreach ($this->classMethods as $classMethod) {
            sscanf($classMethod, '%[^::]::%[^#]#%s', $className, $methodName, $queryArgPosition);

            if ($methodName === $methodReflection->getName() && $className === $methodReflection->getDeclaringClass()->getName()) {
                $unsupportedMethod = false;
                break;
            }
        }

        if ($unsupportedMethod) {
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
