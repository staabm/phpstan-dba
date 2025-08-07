<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\UnresolvableQueryException;

/**
 * @implements Rule<CallLike>
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
        return CallLike::class;
    }

    public function processNode(Node $callLike, Scope $scope): array
    {
        if ($callLike instanceof MethodCall) {
            if (! $callLike->name instanceof Identifier) {
                return [];
            }
            $methodReflection = $scope->getMethodReflection($scope->getType($callLike->var), $callLike->name->toString());
        } elseif ($callLike instanceof StaticCall) {
            if (! $callLike->name instanceof Identifier) {
                return [];
            }
            if (! $callLike->class instanceof Name) {
                return [];
            }
            $classType = $scope->resolveTypeByName($callLike->class);
            $methodReflection = $scope->getMethodReflection($classType, $callLike->name->toString());
        } else {
            return [];
        }

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

        $args = $callLike->getArgs();

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
                        RuleErrorBuilder::message($queryError->asRuleMessage())->identifier('dba.syntaxError')->line($callLike->getStartLine())->build(),
                    ];
                }
            }
        } catch (UnresolvableQueryException $exception) {
            return [
                RuleErrorBuilder::message($exception->asRuleMessage())->tip($exception::getTip())->identifier('dba.unresolvableQuery')->line($callLike->getStartLine())->build(),
            ];
        }

        return [];
    }
}
