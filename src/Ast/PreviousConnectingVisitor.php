<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Ast;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use staabm\PHPStanDba\QueryReflection\DIContainerBridge;
use function array_pop;

final class PreviousConnectingVisitor extends NodeVisitorAbstract
{
    public const ATTRIBUTE_PARENT = 'dba-parent';

    public const ATTRIBUTE_PREVIOUS = 'dba-previous';

    /**
     * @var list<Node>
     */
    private array $stack = [];

    private ?Node $previous;

    // a dummy property to force instantiation of DIContainerBridge
    // for use all over the phpstan-dba codebase
    private DIContainerBridge $containerBridge; // @phpstan-ignore property.onlyWritten

    public function __construct(DIContainerBridge $dummyParameter)
    {
        $this->containerBridge = $dummyParameter;
    }

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
