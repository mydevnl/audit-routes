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
 * The stringable value of either an argument or declared variabele will be marked as a tested route.
 */
class RouteTestTraverser extends NodeVisitorAbstract
{
    /** @var array<int, string> $actingMethods */
    protected array $actingMethods;

    /**
     * @param TestAuditor $auditor
     * @return void
     */
    public function __construct(protected readonly TestAuditor $auditor)
    {
        $this->actingMethods = Config::get('audit-routes.tests.acting-methods');
    }

    /**
     * @param Node $node
     * @return ?int
     */
    public function enterNode(Node $node): ?int
    {
        match ($node::class) {
            Assign::class      => $this->handleVariabeleDeclaration($node),
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
    protected function handleVariabeleDeclaration(Assign $node): ?int
    {
        if (!$node->var instanceof Variable) {
            return null;
        }

        $variabeleName = strval($node->var->name);

        (new NodeTraverser(
            new StringValueTraverser(function (string $value) use ($variabeleName): int {
                $this->auditor->declareVariable($variabeleName, $value);

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
            new VariabeleValueTraverser(function (string $variabeleName): ?int {
                $value = $this->auditor->getDeclaredVariable($variabeleName);

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
