<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Rules;

use PDOStatement;
use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ObjectType;
use staabm\PHPStanDba\PdoReflection\PdoStatementReflection;
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

        return $this->checkErrors($callLike, $scope, $methodReflection);
    }

    /**
     * @param MethodCall|New_ $callLike
     *
     * @return RuleError[]
     */
    private function checkErrors(CallLike $callLike, Scope $scope, ExtendedMethodReflection $methodReflection): array
    {
        $args = $callLike->getArgs();

        if (\count($args) < 1) {
            return [];
        }

        $queryReflection = new QueryReflection();

        if (PDOStatement::class === $methodReflection->getDeclaringClass()->getName()
            && 'execute' === strtolower($methodReflection->getName())
            && $callLike instanceof Methodcall
        ) {
            $stmtReflection = new PdoStatementReflection();
            $queryExpr = $stmtReflection->findPrepareQueryStringExpression($callLike);
            $parameterTypes = $scope->getType($args[0]->value);
        } else {
            $queryExpr = $args[0]->value;
            $parameterTypes = \count($args) > 1 ? $scope->getType($args[1]->value) : null;
        }

        if (null === $queryExpr) {
            return [];
        }
        if ($queryReflection->isResolvable($queryExpr, $scope)->no()) {
            return [];
        }

        $parameters = null;
        if ($parameterTypes !== null) {
            try {
                $parameters = $queryReflection->resolveParameters($parameterTypes) ?? [];
            } catch (UnresolvableQueryException $exception) {
                return [
                    RuleErrorBuilder::message($exception->asRuleMessage())->tip($exception::getTip())->line($callLike->getLine())->build(),
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
                $ruleErrors[] = RuleErrorBuilder::message($error)->line($callLike->getLine())->build();
            }

            return $ruleErrors;
        } catch (UnresolvableQueryException $exception) {
            return [
                RuleErrorBuilder::message($exception->asRuleMessage())->tip($exception::getTip())->line($callLike->getLine())->build(),
            ];
        }
    }
}
