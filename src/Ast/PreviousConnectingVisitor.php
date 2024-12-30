<?php

declare(strict_types=1);

namespace staabm\PHPStanDba\Ast;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Node\VirtualNode;
use staabm\PHPStanDba\QueryReflection\DIContainerBridge;

final class PreviousConnectingVisitor extends NodeVisitorAbstract
{
    public const ATTRIBUTE_PREVIOUS = 'dba-previous';

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
        $this->previous = null;

        return null;
    }

    public function enterNode(Node $node)
    {
        if (
            null !== $this->previous
            && ! $this->previous instanceof Node\FunctionLike
            && ! $this->previous instanceof Node\Stmt\ClassLike
            && ! $this->previous instanceof VirtualNode
        ) {
            $node->setAttribute(self::ATTRIBUTE_PREVIOUS, \WeakReference::create($this->previous));
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        $this->previous = $node;

        return null;
    }
}
