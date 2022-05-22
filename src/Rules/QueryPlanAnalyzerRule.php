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
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use staabm\PHPStanDba\Analyzer\QueryPlanAnalyzerMysql;
use staabm\PHPStanDba\QueryReflection\PlaceholderValidation;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\QueryResolver;
use staabm\PHPStanDba\UnresolvableQueryException;

/**
 * @implements Rule<CallLike>
 */
final class QueryPlanAnalyzerRule implements Rule
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
            if (!$callLike->name instanceof Node\Identifier) {
                return [];
            }

            $methodReflection = $scope->getMethodReflection($scope->getType($callLike->var), $callLike->name->toString());
        } elseif ($callLike instanceof New_) {
            if (!$callLike->class instanceof FullyQualified) {
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

            if ($methodName === $methodReflection->getName() &&
                ($methodReflection->getDeclaringClass()->getName() === $className || $methodReflection->getDeclaringClass()->isSubclassOf($className))) {
                $unsupportedMethod = false;
                break;
            }
        }

        if ($unsupportedMethod) {
            return [];
        }

        return $this->analyze($callLike, $scope);
    }

    /**
     * @param MethodCall|New_ $callLike
     *
     * @return RuleError[]
     */
    private function analyze(CallLike $callLike, Scope $scope): array
    {
        $args = $callLike->getArgs();

        if (\count($args) < 1) {
            return [];
        }

        $queryExpr = $args[0]->value;

        if ($scope->getType($queryExpr) instanceof MixedType) {
            return [];
        }

        $parameterTypes = null;
        if (\count($args) > 1) {
            $parameterTypes = $scope->getType($args[1]->value);
        }

        $errors = [];
        $queryReflection = new QueryReflection();
        foreach($queryReflection->analyzeQueryPlan($scope, $queryExpr, $parameterTypes) as $queryPlanResult) {
            $notUsingIndex = $queryPlanResult->getTablesNotUsingIndex();
            if (count($notUsingIndex)> 0) {
                foreach($notUsingIndex as $table) {
                    $errors[] = sprintf('Query plan analyzer: table "%s" is not using an index', $table);
                }

            } else {
                foreach($queryPlanResult->getTablesNotEfficient() as $table) {
                    $errors[] = sprintf('Query plan analyzer: in efficient index use in table "%s"', $table);
                }
            }
        }

        $ruleErrors = [];
        foreach ($errors as $error) {
            $ruleErrors[] = RuleErrorBuilder::message($error)->line($callLike->getLine())->build();
        }

        return $ruleErrors;
    }

}
