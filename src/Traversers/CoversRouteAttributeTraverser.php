<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Traversers;

use MyDev\AuditRoutes\Attributes\CoversRoute;
use MyDev\AuditRoutes\Auditors\TestAuditor;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;

/**
 * Discover if a testing class or method contains the CoversRoute attribute.
 * The provided arguments in the attribute are marked as tested routes.
 */
class CoversRouteAttributeTraverser extends NodeVisitorAbstract
{
    /**
     * @param TestAuditor $auditor
     * @return void
     */
    public function __construct(protected readonly TestAuditor $auditor)
    {
    }

    /**
     * @param Node $node
     * @return ?int
     */
    public function enterNode(Node $node): ?int
    {
        if (!$node instanceof Attribute) {
            return null;
        }

        if (!in_array($node->name->name, ['CoversRoute', CoversRoute::class])) {
            return null;
        }

        (new NodeTraverser(
            new StringValueTraverser(function (string $value): int {
                $this->auditor->markRouteOccurrence($value);

                return NodeVisitor::STOP_TRAVERSAL;
            })
        ))->traverse($node->args);

        return NodeVisitor::DONT_TRAVERSE_CHILDREN;
    }
}
