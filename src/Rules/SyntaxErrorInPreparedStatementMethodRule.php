<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ObjectType;
use staabm\PHPStanDba\QueryReflection\PlaceholderValidation;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\UnresolvableQueryException;

/**
 * @implements Rule<CallLike>
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
        return CallLike::class;
    }

    public function processNode(Node $callLike, Scope $scope): array
    {
        if ($callLike instanceof MethodCall) {
            if (! $callLike->name instanceof Node\Identifier) {
                return [];
            }

            $methodReflection = $scope->getMethodReflection($scope->getType($callLike->var), $callLike->name->toString());
        } elseif ($callLike instanceof New_) {
            if (! $callLike->class instanceof FullyQualified) {
                return [];
            }
            $methodReflection = $scope->getMethodReflection(new ObjectType($callLike->class->toCodeString()), '__construct');
        } else {
            return [];
        }

        if (null === $methodReflection) {
            return [];
        }

        $unsupportedMethod = true;
        foreach ($this->classMethods as $classMethod) {
            sscanf($classMethod, '%[^::]::%s', $className, $methodName);
            if (! \is_string($className) || ! \is_string($methodName)) {
                throw new ShouldNotHappenException('Invalid classMethod definition');
            }

            if ($methodName === $methodReflection->getName() &&
                ($methodReflection->getDeclaringClass()->getName() === $className || $methodReflection->getDeclaringClass()->isSubclassOf($className))) {
                $unsupportedMethod = false;
                break;
            }
        }

        if ($unsupportedMethod) {
            return [];
        }

        return $this->checkErrors($callLike, $scope);
    }

    /**
     * @param MethodCall|New_ $callLike
     *
     * @return RuleError[]
     */
    private function checkErrors(CallLike $callLike, Scope $scope): array
    {
        $args = $callLike->getArgs();

        if (\count($args) < 1) {
            return [];
        }

        $queryExpr = $args[0]->value;
        $queryReflection = new QueryReflection();

        if ($queryReflection->isResolvable($queryExpr, $scope)->no()) {
            return [];
        }

        $parameters = null;
        if (\count($args) > 1) {
            $parameterTypes = $scope->getType($args[1]->value);
            try {
                $parameters = $queryReflection->resolveParameters($parameterTypes) ?? [];
            } catch (UnresolvableQueryException $exception) {
                return [
                    RuleErrorBuilder::message($exception->asRuleMessage())->tip($exception::getTip())->line($callLike->getStartLine())->build(),
                ];
            }
        }

        if (null === $parameters) {
            $queryStrings = $queryReflection->resolveQueryStrings($queryExpr, $scope);
        } else {
            $queryStrings = $queryReflection->resolvePreparedQueryStrings($queryExpr, $parameterTypes, $scope);
        }

        $errors = [];
        try {
            foreach ($queryStrings as $queryString) {
                $queryError = $queryReflection->validateQueryString($queryString);
                if (null !== $queryError) {
                    $error = $queryError->asRuleMessage();
                    $errors[$error] = $error;
                }
            }

            if (null !== $parameters) {
                $placeholderValidation = new PlaceholderValidation();
                foreach ($placeholderValidation->checkQuery($queryExpr, $scope, $parameters) as $error) {
                    // make error messages unique
                    $errors[$error] = $error;
                }
            }

            $ruleErrors = [];
            foreach ($errors as $error) {
                $ruleErrors[] = RuleErrorBuilder::message($error)->line($callLike->getStartLine())->build();
            }

            return $ruleErrors;
        } catch (UnresolvableQueryException $exception) {
            return [
                RuleErrorBuilder::message($exception->asRuleMessage())->tip($exception::getTip())->line($callLike->getStartLine())->build(),
            ];
        }
    }
}
