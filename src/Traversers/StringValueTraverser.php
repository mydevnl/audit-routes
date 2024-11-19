<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Traversers;

use Closure;
use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;

class StringValueTraverser extends NodeVisitorAbstract
{
    /**
     * @param Closure(string): ?int $callback
     * @return void
     */
    public function __construct(protected Closure $callback)
    {
    }

    /**
     * @param Node $node
     * @return ?int
     */
    public function enterNode(Node $node): ?int
    {
        if (!$node instanceof String_) {
            return null;
        }

        return call_user_func($this->callback, $node->value);
    }
}
