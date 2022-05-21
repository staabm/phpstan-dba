<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\QueryReflection;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\NodeFinder;
use PHPStan\ShouldNotHappenException;

final class ExpressionFinder
{
    /**
     * Do not change, part of internal PHPStan naming.
     *
     * @var string
     */
    private const PREVIOUS = 'previous';

    /**
     * Convention key name in php-parser and PHPStan for parent node.
     *
     * @var string
     */
    private const PARENT = 'parent';

    private NodeFinder $nodeFinder;

    public function __construct()
    {
        $this->nodeFinder = new NodeFinder();
    }

    /**
     * @param Variable|MethodCall $expr
     */
    public function findQueryStringExpression(Expr $expr): ?Expr
    {
        $current = $expr;
        while (null !== $current) {
            /** @var Assign|null $assign */
            $assign = $this->findFirstPreviousOfNode($current, function ($node) {
                return $node instanceof Assign;
            });

            if (null !== $assign) {
                if ($expr instanceof Variable && $this->resolveName($assign->var) === $this->resolveName($expr)) {
                    return $assign->expr;
                }
                if ($expr instanceof MethodCall && $this->resolveName($assign->var) === $this->resolveName($expr->var)) {
                    return $assign->expr;
                }
            }

            $current = $assign;
        }

        return null;
    }

    /**
     * @param MethodCall $expr
     *
     * @return MethodCall[]
     */
    public function findBindCalls(Expr $expr): array
    {
        $result = [];
        $current = $expr;
        while (null !== $current) {
            /** @var Assign|MethodCall|null $call */
            $call = $this->findFirstPreviousOfNode($current, function ($node) {
                return $node instanceof MethodCall || $node instanceof Assign;
            });

            if (null !== $call && $this->resolveName($call->var) === $this->resolveName($expr->var)) {
                if ($call instanceof Assign) { // found the prepare call
                    return $result;
                }

                $name = $this->resolveName($call);

                if (null !== $name && \in_array(strtolower($name), ['bindparam', 'bindvalue'], true)) {
                    $result[] = $call;
                }
            }

            $current = $call;
        }

        return $result;
    }

    /**
     * XXX use astral simpleNameResolver instead.
     *
     * @param Expr|Variable|MethodCall $node
     *
     * @return string|null
     */
    private function resolveName($node)
    {
        if (property_exists($node, 'name')) {
            if (\is_string($node->name)) {
                return $node->name;
            }

            if ($node->name instanceof Node\Identifier) {
                return $node->name->toString();
            }
        }

        return null;
    }

    /**
     * @param callable(Node $node):bool $filter
     */
    private function findFirstPreviousOfNode(Node $node, callable $filter): ?Node
    {
        // move to previous expression
        $previousStatement = $node->getAttribute(self::PREVIOUS);
        if (null !== $previousStatement) {
            if (!$previousStatement instanceof Node) {
                throw new ShouldNotHappenException();
            }
            $foundNode = $this->findFirst([$previousStatement], $filter);
            // we found what we need
            if (null !== $foundNode) {
                return $foundNode;
            }

            return $this->findFirstPreviousOfNode($previousStatement, $filter);
        }

        $parent = $node->getAttribute(self::PARENT);
        if ($parent instanceof FunctionLike) {
            return null;
        }

        if ($parent instanceof Node) {
            return $this->findFirstPreviousOfNode($parent, $filter);
        }

        return null;
    }

    /**
     * @param Node|Node[] $nodes
     * @param callable(Node $node):bool $filter
     */
    private function findFirst(/*Node|array*/ $nodes, callable $filter): ?Node
    {
        return $this->nodeFinder->findFirst($nodes, $filter);
    }
}
