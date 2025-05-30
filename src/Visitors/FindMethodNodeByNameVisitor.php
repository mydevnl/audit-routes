<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Visitors;

use MyDev\AuditRoutes\Entities\NodeAccessor;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use ReflectionException;

class FindMethodNodeByNameVisitor extends NodeVisitorAbstract
{
    /** @var NodeAccessor|null */
    protected ?NodeAccessor $nodeAccessor = null;

    /**
     * @param string $methodName
     * @return void
     */
    public function __construct(protected string $methodName)
    {
    }

    /**
     * @param Node $node
     * @return int|null
     *
     * @throws ReflectionException
     */
    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Node\Stmt\Class_) {
            $this->nodeAccessor = (new NodeAccessor($node))->find(
                fn (ClassMethod $node): bool => strval($node->name) === $this->methodName,
            );

            return NodeVisitor::STOP_TRAVERSAL;
        }

        return null;
    }

    /** @return NodeAccessor|null */
    public function getNodeAccessor(): ?NodeAccessor
    {
        return $this->nodeAccessor;
    }
}
