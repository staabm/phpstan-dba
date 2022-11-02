<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Ast;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\NodeFinder;
use PHPStan\ShouldNotHappenException;

final class ExpressionFinder
{
    /**
     * @var NodeFinder
     */
    private $nodeFinder;

    public function __construct()
    {
        $this->nodeFinder = new NodeFinder();
    }

    /**
     * @param Variable|MethodCall $expr
     *
     * @deprecated use findAssignmentExpression() instead
     */
    public function findQueryStringExpression(Expr $expr): ?Expr
    {
        return $this->findAssignmentExpression($expr);
    }

    /**
     * @param Variable|MethodCall $expr
     */
    public function findAssignmentExpression(Expr $expr, bool $skipAssignOps = false): ?Expr
    {
        $current = $expr;
        while (null !== $current) {
            $matchedAssignOp = false;

            /** @var Assign|null $assign */
            $assign = $this->findFirstPreviousOfNode($current, function ($node) use (&$matchedAssignOp) {
                if ($node instanceof Node\Stmt\Expression && $node->expr instanceof AssignOp) {
                    $matchedAssignOp = true;
                }

                return $node instanceof Assign;
            });

            if ($skipAssignOps && $matchedAssignOp) {
                return null;
            }

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
     * XXX At best, this method would be implemented in NodeConnectingVisitor, and the 'firstPreviousAssign' would be directly available.
     *
     * @param callable(Node $node):bool $filter
     */
    private function findFirstPreviousOfNode(Node $node, callable $filter): ?Node
    {
        // move to previous expression
        $previousStatement = $node->getAttribute(PreviousConnectingVisitor::ATTRIBUTE_PREVIOUS);
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

        $parent = $node->getAttribute(PreviousConnectingVisitor::ATTRIBUTE_PARENT);
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
