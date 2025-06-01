<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Entities;

use Closure;
use MyDev\AuditRoutes\Visitors\CallbackVisitor;
use PhpParser\Node;
use PhpParser\NodeAbstract;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use PhpParser\PrettyPrinter\Standard;
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

    /** @return string */
    public function getName(): string
    {
        $name = $this->node;
        if (property_exists($this->node, 'name')) {
            $name = $this->node->name;
        }

        if ($name instanceof Node\Expr) {
            $printer = new Standard();

            return $printer->prettyPrintExpr($name);
        }

        if (!is_string($name)) {
            return strval($name);
        }

        return $name;
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
     * @param class-string | Closure ...$filters
     * @return bool
     */
    public function has(string | Closure ...$filters): bool
    {
        return boolval($this->find(...$filters));
    }

    /**
     * @param class-string | Closure ...$filters
     * @return null | self
     */
    public function find(string | Closure ...$filters): ?self
    {
        $nodes = $this->filter($filters[0]);

        $filters = array_slice($filters, 1);

        if (empty($filters)) {
            return $nodes[0] ?? null;
        }

        foreach ($nodes as $node) {
            $found = $node->find(...$filters);
            if ($found) {
                return $found;
            }
        }

        return null;
    }

    /**
     * @param class-string | Closure(Node): bool $filter
     * @param null | int $returnValue
     * @return array<int, self>
     */
    public function filter(
        string | Closure $filter,
        ?int $returnValue = NodeVisitor::DONT_TRAVERSE_CHILDREN,
    ): array {
        $this->filterResults = [];

        $requiredInstance = $filter;
        if (is_callable($filter)) {
            try {
                $parameters = (new ReflectionFunction($filter))->getParameters();
            } catch (ReflectionException) {
                $parameters = [];
            }
            $requiredInstance = empty($parameters) ? null : strval($parameters[0]->getType());
        }

        $this->traverse(new CallbackVisitor(
            function (NodeAbstract $node) use ($requiredInstance, $filter, $returnValue): ?int {
                if ($requiredInstance && !$node instanceof $requiredInstance) {
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
     * @param Closure(Node): mixed $callback
     * @param null | int $returnValue
     * @return void
     */
    public function each(
        Closure $callback,
        ?int $returnValue = NodeVisitor::DONT_TRAVERSE_CHILDREN,
    ): void {
        try {
            $parameters = (new ReflectionFunction($callback))->getParameters();
        } catch (ReflectionException) {
            $parameters = [];
        }
        $requiredInstance = empty($parameters) ? null : strval($parameters[0]->getType());

        $this->traverse(new CallbackVisitor(
            function (NodeAbstract $node) use ($callback, $requiredInstance, $returnValue): ?int {
                if ($requiredInstance && !$node instanceof $requiredInstance) {
                    return null;
                }

                $callback($node);

                return $returnValue;
            },
        ));
    }
}
