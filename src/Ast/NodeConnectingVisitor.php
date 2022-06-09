<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Ast;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use function array_pop;
use function count;
use function get_class;

final class NodeConnectingVisitor extends NodeVisitorAbstract
{
    const ATTRIBUTE_PARENT = 'dba-parent';
    const ATTRIBUTE_PREVIOUS = 'dba-previous';
    
    /**
     * @var Node[]
     */
    private $stack = [];

    /**
     * @var ?Node
     */
    private $previous;

    public function beforeTraverse(array $nodes) {
        $this->stack    = [];
        $this->previous = null;
    }

    public function enterNode(Node $node) {
        if (!empty($this->stack)) {
            $node->setAttribute(self::ATTRIBUTE_PARENT, $this->stack[count($this->stack) - 1]);
        }

        if ($this->previous !== null && $this->previous->getAttribute(self::ATTRIBUTE_PARENT) === $node->getAttribute(self::ATTRIBUTE_PARENT)) {
            $node->setAttribute(self::ATTRIBUTE_PREVIOUS, $this->previous);
        }

        $this->stack[] = $node;
    }

    public function leaveNode(Node $node) {
        $this->previous = $node;

        array_pop($this->stack);
    }
}
