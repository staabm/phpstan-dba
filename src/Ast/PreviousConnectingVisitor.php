<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Ast;

use function array_pop;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class PreviousConnectingVisitor extends NodeVisitorAbstract
{
    public const ATTRIBUTE_PARENT = 'dba-parent';
    public const ATTRIBUTE_PREVIOUS = 'dba-previous';

    /**
     * @var list<Node>
     */
    private $stack = [];

    /**
     * @var ?Node
     */
    private $previous;

    public function beforeTraverse(array $nodes)
    {
        $this->stack = [];
        $this->previous = null;

        return null;
    }

    public function enterNode(Node $node)
    {
        if ([] !== $this->stack) {
            $node->setAttribute(self::ATTRIBUTE_PARENT, $this->stack[\count($this->stack) - 1]);
        }

        if (null !== $this->previous && $this->previous->getAttribute(self::ATTRIBUTE_PARENT) === $node->getAttribute(self::ATTRIBUTE_PARENT)) {
            $node->setAttribute(self::ATTRIBUTE_PREVIOUS, $this->previous);
        }

        $this->stack[] = $node;

        return null;
    }

    public function leaveNode(Node $node)
    {
        $this->previous = $node;

        array_pop($this->stack);

        return null;
    }
}
