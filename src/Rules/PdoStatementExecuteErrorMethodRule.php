<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Rules;

use PDO;
use PDOStatement;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use staabm\PHPStanDba\QueryReflection\QueryReflection;

/**
 * @implements Rule<MethodCall>
 *
 * @see PdoStatementExecuteErrorMethodRuleTest
 */
final class PdoStatementExecuteErrorMethodRule implements Rule
{
    /**
     * @param list<string> $classMethods
     */
    public function __construct(array $classMethods)
    {
        $this->classMethods = $classMethods;
    }

    public function getNodeType(): string
    {
        return PDOStatement::class;
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

		if ('execute' !== $methodReflection->getName()) {
			return [];
		}

        $args = $node->getArgs();
        $errors = [];

        $queryReflection = new QueryReflection();
        $queryString = $queryReflection->resolveQueryString($args[0]->value, $scope);
        if (null === $queryString) {
            return $errors;
        }

        $error = $queryReflection->validateQueryString($queryString);
        if (null !== $error) {
            $errors[] = RuleErrorBuilder::message('Query error: '.$error->getMessage().' ('.$error->getCode().').')->line($node->getLine())->build();
        }

        return $errors;
    }
}
