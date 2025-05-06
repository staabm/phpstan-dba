<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\UnresolvableQueryException;

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
    public array $classMethods;

    private ReflectionProvider $reflectionProvider;

    /**
     * @param list<string> $classMethods
     */
    public function __construct(array $classMethods, ReflectionProvider $reflectionProvider)
    {
        $this->classMethods = $classMethods;
        $this->reflectionProvider = $reflectionProvider;
    }

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

        $queryArgPosition = null;
        $unsupportedMethod = true;
        foreach ($this->classMethods as $classMethod) {
            sscanf($classMethod, '%[^::]::%[^#]#%i', $className, $methodName, $queryArgPosition);
            if (! \is_string($className) || ! \is_string($methodName) || ! \is_int($queryArgPosition)) {
                throw new ShouldNotHappenException('Invalid classMethod definition');
            }

            if ($methodName === $methodReflection->getName() &&
                (
                    $methodReflection->getDeclaringClass()->getName() === $className
                    || ($this->reflectionProvider->hasClass($className) && $methodReflection->getDeclaringClass()->isSubclassOfClass($this->reflectionProvider->getClass($className)))
                )
            ) {
                $unsupportedMethod = false;
                break;
            }
        }

        if (null === $queryArgPosition) {
            throw new ShouldNotHappenException('Invalid classMethod definition');
        }
        if ($unsupportedMethod) {
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
            $queryStrings = $queryReflection->resolveQueryStrings($queryExpr, $scope);
            foreach ($queryStrings as $queryString) {
                $queryError = $queryReflection->validateQueryString($queryString);
                if (null !== $queryError) {
                    return [
                        RuleErrorBuilder::message($queryError->asRuleMessage())->identifier('dba.syntaxError')->line($node->getStartLine())->build(),
                    ];
                }
            }
        } catch (UnresolvableQueryException $exception) {
            return [
                RuleErrorBuilder::message($exception->asRuleMessage())->tip($exception::getTip())->identifier('dba.unresolvableQuery')->line($node->getStartLine())->build(),
            ];
        }

        return [];
    }
}
