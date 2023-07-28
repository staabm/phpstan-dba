<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Rules;

use PDOStatement;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use staabm\PHPStanDba\PdoReflection\PdoStatementReflection;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\Tests\QueryPlanAnalyzerRuleTest;
use staabm\PHPStanDba\UnresolvableQueryException;

/**
 * @implements Rule<MethodCall>
 *
 * @see QueryPlanAnalyzerPdoExecuteRuleTest
 */
final class QueryPlanAnalyzerPdoExecuteRule implements Rule
{
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

        if (PDOStatement::class !== $methodReflection->getDeclaringClass()->getName()) {
            return [];
        }

        if ('execute' !== strtolower($methodReflection->getName())) {
            return [];
        }

        try {
            return $this->analyze($node, $scope);
        } catch (UnresolvableQueryException $exception) {
            return [
                RuleErrorBuilder::message($exception->asRuleMessage())->tip($exception::getTip())->line($node->getLine())->build(),
            ];
        }
    }

    /**
     * @return RuleError[]
     */
    private function analyze(Node $node, Scope $scope): array
    {
        $args = $node->getArgs();

        if (\count($args) < 1) {
            return [];
        }

        if (false === QueryReflection::getRuntimeConfiguration()->getNumberOfAllowedUnindexedReads()) {
            return [];
        }

        $stmtReflection = new PdoStatementReflection();
        $queryExpr = $stmtReflection->findPrepareQueryStringExpression($node);

        $queryReflection = new QueryReflection();
        if ($queryReflection->isResolvable($queryExpr, $scope)->no()) {
            return [];
        }

        $parameterTypes = $scope->getType($args[0]->value);
        if (!$parameterTypes->isArray() || $parameterTypes->isEmpty()) {
            return [];
        }

        $ruleErrors = [];
        $proposal = "\n\nConsider optimizing the query.\nIn some cases this is not a problem and this error should be ignored.";

        foreach ($queryReflection->analyzeQueryPlan($scope, $queryExpr, $parameterTypes) as $queryPlanResult) {
            $suffix = $proposal;
            if (QueryReflection::getRuntimeConfiguration()->isDebugEnabled()) {
                $suffix = $proposal . "\n\nSimulated query: " . $queryPlanResult->getSimulatedQuery();
            }

            $notUsingIndex = $queryPlanResult->getTablesNotUsingIndex();
            if (\count($notUsingIndex) > 0) {
                foreach ($notUsingIndex as $table) {
                    $ruleErrors[] = RuleErrorBuilder::message(
                        sprintf(
                            "Query is not using an index on table '%s'." . $suffix,
                            $table
                        )
                    )
                        ->line($node->getLine())
                        ->tip('see Mysql Docs https://dev.mysql.com/doc/refman/8.0/en/select-optimization.html')
                        ->build();
                }
            } else {
                foreach ($queryPlanResult->getTablesDoingTableScan() as $table) {
                    $ruleErrors[] = RuleErrorBuilder::message(
                        sprintf(
                            "Query is using a full-table-scan on table '%s'." . $suffix,
                            $table
                        )
                    )
                        ->line($node->getLine())
                        ->tip('see Mysql Docs https://dev.mysql.com/doc/refman/8.0/en/table-scan-avoidance.html')
                        ->build();
                }

                foreach ($queryPlanResult->getTablesDoingUnindexedReads() as $table) {
                    $ruleErrors[] = RuleErrorBuilder::message(
                        sprintf(
                            "Query is triggering too many unindexed-reads on table '%s'." . $suffix,
                            $table
                        )
                    )
                        ->line($node->getLine())
                        ->tip('see Mysql Docs https://dev.mysql.com/doc/refman/8.0/en/select-optimization.html')
                        ->build();
                }
            }
        }

        return $ruleErrors;
    }
}
