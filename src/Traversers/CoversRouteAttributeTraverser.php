<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Traversers;

use MyDev\AuditRoutes\Attributes\CoversRoute;
use MyDev\AuditRoutes\Auditors\TestAuditor;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;

/**
 * Discover if a testing class or method contains the CoversRoute attribute.
 * The provided arguments in the attribute are marked as tested routes.
 */
class CoversRouteAttributeTraverser extends NodeVisitorAbstract
{
    public function __construct(private readonly TestAuditor $auditor)
    {
    }

    public function enterNode(Node $node): null | int
    {
        if (!$node instanceof Attribute) {
            return null;
        }

        if (!in_array($node->name->name, ['CoversRoute', CoversRoute::class])) {
            return null;
        }

        $this->traverseAttributeArguments($node);

        return NodeVisitor::DONT_TRAVERSE_CHILDREN;
    }

    protected function traverseAttributeArguments(Attribute $node): void
    {
        (new NodeTraverser(new class ($this->auditor) extends NodeVisitorAbstract {
            public function __construct(private readonly TestAuditor $auditor)
            {
            }
        
            public function enterNode(Node $node): null | int
            {
                if ($node instanceof String_) {
                    $this->auditor->markRouteOccurrence($node->value);
                }
        
                return null;
            }
        }))->traverse($node->args);
    }
}
