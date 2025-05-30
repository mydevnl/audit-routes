<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Visitors;

use Illuminate\Support\Facades\Config;
use MyDev\AuditRoutes\Contracts\RouteOccurrenceTrackerInterface;
use MyDev\AuditRoutes\Contracts\VariableTrackerInterface;
use MyDev\AuditRoutes\Entities\NodeAccessor;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use ReflectionException;

class PhpUnitMethodVisitor extends NodeVisitorAbstract
{
    /** @param VariableTrackerInterface&RouteOccurrenceTrackerInterface $tracker */
    public function __construct(protected VariableTrackerInterface&RouteOccurrenceTrackerInterface $tracker)
    {
    }

    /**
     * @param ClassMethod $node
     * @return int | null
     *
     * @throws ReflectionException
     */
    public function enterNode(Node $node): ?int
    {
        $actingMethod = (new NodeAccessor($node))
            ->find(fn (MethodCall $node): bool => in_array(
                strval($node->name),
                Config::array('audit-routes.tests.acting-methods'),
            ));

        if (is_null($actingMethod)) {
            return NodeVisitor::DONT_TRAVERSE_CHILDREN;
        }

        $actingMethod->traverse(
            new StringValueVisitor(function (string $value): int {
                $this->tracker->markRouteOccurrence($value);

                return NodeVisitor::STOP_TRAVERSAL;
            }),
            new VariableValueVisitor(function (string $variableName): ?int {
                $value = $this->tracker->getDeclaredVariable($variableName);

                if (!$value) {
                    return null;
                }

                $this->tracker->markRouteOccurrence($value);

                return NodeVisitor::STOP_TRAVERSAL;
            }),
        );

        return NodeVisitor::DONT_TRAVERSE_CHILDREN;
    }
}
