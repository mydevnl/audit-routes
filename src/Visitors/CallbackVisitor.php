<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Visitors;

use Closure;
use PhpParser\Node;
use PhpParser\NodeAbstract;
use PhpParser\NodeVisitorAbstract;

class CallbackVisitor extends NodeVisitorAbstract
{
    /**
     * @param Closure(NodeAbstract): ?int $callback
     * @return void
     */
    public function __construct(protected Closure $callback)
    {
    }

    /**
     * @param NodeAbstract $node
     * @return null | int
     */
    public function enterNode(Node $node): ?int
    {
        return call_user_func($this->callback, $node);
    }
}
