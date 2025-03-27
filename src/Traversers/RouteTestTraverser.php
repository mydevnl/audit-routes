<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Traversers;

use Illuminate\Support\Facades\Config;
use MyDev\AuditRoutes\Auditors\TestAuditor;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;

/**
 * Discover if a testing method contains an acting method receiving a route as first argument.
 * The stringable value of either an argument or declared variable will be marked as a tested route.
 */
class RouteTestTraverser extends NodeVisitorAbstract
{
    /** @var array<int, string> $actingMethods */
    protected array $actingMethods;

    /**
     * @param TestAuditor $auditor
     * @return void
     */
    public function __construct(protected TestAuditor $auditor)
    {
        $this->actingMethods = Config::array('audit-routes.tests.acting-methods');
    }

    /**
     * @param Node $node
     * @return null | int
     */
    public function enterNode(Node $node): ?int
    {
        match ($node::class) {
            Assign::class      => $this->handleVariableDeclaration($node),
            MethodCall::class  => $this->handleMethodCall($node),
            ClassMethod::class => $this->auditor->resetDeclaredVariables(),
            default            => null,
        };

        return null;
    }

    /**
     * @param Assign $node
     * @return int | null
     */
    protected function handleVariableDeclaration(Assign $node): ?int
    {
        if (!$node->var instanceof Variable) {
            return null;
        }

        /** @var string | \Stringable $name */
        $name = $node->var->name;
        $variableName = strval($name);

        (new NodeTraverser(
            new StringValueTraverser(function (string $value) use ($variableName): int {
                $this->auditor->declareVariable($variableName, $value);

                return NodeVisitor::STOP_TRAVERSAL;
            })
        ))->traverse([$node->expr]);

        return NodeVisitor::DONT_TRAVERSE_CHILDREN;
    }

    /**
     * @param MethodCall $node
     * @return int | null
     */
    protected function handleMethodCall(MethodCall $node): ?int
    {
        if (!method_exists($node->name, 'toString')) {
            return null;
        }

        if (!in_array($node->name->toString(), $this->actingMethods)) {
            return null;
        }

        if (empty($node->args[0])) {
            return null;
        }

        (new NodeTraverser(
            new StringValueTraverser(function (string $value): int {
                $this->auditor->markRouteOccurrence($value);

                return NodeVisitor::STOP_TRAVERSAL;
            }),
            new VariableValueTraverser(function (string $variableName): ?int {
                $value = $this->auditor->getDeclaredVariable($variableName);

                if (!$value) {
                    return null;
                }

                $this->auditor->markRouteOccurrence($value);

                return NodeVisitor::STOP_TRAVERSAL;
            }),
        ))->traverse([$node->args[0]]);

        return NodeVisitor::DONT_TRAVERSE_CHILDREN;
    }
}
