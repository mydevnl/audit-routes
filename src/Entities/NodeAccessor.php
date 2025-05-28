<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Entities;

use MyDev\AuditRoutes\Visitors\CallbackVisitor;
use PhpParser\Node;
use PhpParser\NodeAbstract;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use Closure;
use ReflectionException;
use ReflectionFunction;

class NodeAccessor
{
    /** @var array<int, self> */
    protected array $filterResults;

    /** @param Node $node */
    public function __construct(protected readonly Node $node)
    {
    }

    /** @return Node */
    public function getNode(): Node
    {
        return $this->node;
    }

    /**
     * @param NodeVisitorAbstract ...$visitors
     * @return void
     */
    public function traverse(NodeVisitorAbstract ...$visitors): void
    {
        (new NodeTraverser(...$visitors))->traverse([$this->node]);
    }

    /**
     * @param class-string<NodeAbstract> | Closure(NodeAbstract): mixed $filter
     * @return bool
     * @throws ReflectionException
     */
    public function has(string | Closure $filter): bool
    {
        return boolval($this->find($filter));
    }

    /**
     * @param class-string<NodeAbstract> | Closure(object): mixed $filter
     * @return null | self
     * @throws ReflectionException
     */
    public function find(string | Closure $filter): ?self
    {
        $result = $this->filter($filter, NodeVisitor::STOP_TRAVERSAL);

        return $result[0] ?? null;
    }

    /**
     * @param class-string<NodeAbstract> | Closure(object): mixed $filter
     * @param null | int $returnValue
     * @return array<int, self>
     * @throws ReflectionException
     */
    public function filter(
        string | Closure $filter,
        ?int $returnValue = NodeVisitor::DONT_TRAVERSE_CHILDREN
    ): array {
        $this->filterResults = [];

        $requiredInstance = $filter;
        if (is_callable($filter)) {
            $parameters = (new ReflectionFunction($filter))->getParameters();
            $requiredInstance = empty($parameters) ? null : strval($parameters[0]->getType());
        }

        $this->traverse(new CallbackVisitor(
            function (NodeAbstract $node) use ($requiredInstance, $filter, $returnValue): ?int {
                if (!$node instanceof $requiredInstance) {
                    return null;
                }
                if (is_callable($filter) && !$filter($node)) {
                    return null;
                }
                $this->filterResults[] = new self($node);

                return $returnValue;
            },
        ));

        return $this->filterResults;
    }

    /**
     * @param Closure(object): mixed $callback
     * @param null | int $returnValue
     * @return void
     * @throws ReflectionException
     */
    public function each(
        Closure $callback,
        ?int $returnValue = NodeVisitor::DONT_TRAVERSE_CHILDREN
    ): void {
        [$firstParameter] = (new ReflectionFunction($callback))->getParameters();
        $requiredInstance = strval($firstParameter->getType());

        $this->traverse(new CallbackVisitor(
            function (NodeAbstract $node) use ($callback, $requiredInstance, $returnValue): ?int {
                if (!$node instanceof $requiredInstance) {
                    return null;
                }

                $callback($node);

                return $returnValue;
            },
        ));
    }
}
