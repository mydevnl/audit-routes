<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Traversers;

use Illuminate\Support\Facades\Config;
use MyDev\AuditRoutes\Auditors\TestAuditor;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;

/**
 * Discover if a testing method calls the route helper within an acting method.
 * The first argument provided to the route helper is marked as a tested route.
 */
class RouteTestTraverser extends NodeVisitorAbstract
{
    protected string $routeMethod;

    /** @var array<int, string> $actingMethods */
    protected array $actingMethods;

    /**
     * @param TestAuditor $auditor
     * @return void
     */
    public function __construct(private readonly TestAuditor $auditor)
    {
        $this->routeMethod = Config::get('audit-routes.tests.route-method');
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
            ClassMethod::class => $this->auditor->resetDeclaredVariables(),
            MethodCall::class  => $this->handleMethodCall($node),
            default            => null,
        };

        return null;
    }

    /**
     * @param Assign $node
     * @return void
     */
    protected function handleVariabeleDeclaration(Assign $node): void
    {
        if (!$node->var instanceof Variable || !$node->expr instanceof String_) {
            return;
        }

        $this->auditor->declareVariable(strval($node->var->name), $node->expr->value);
    }

    /**
     * @param MethodCall $node
     * @return void
     */
    protected function handleMethodCall(MethodCall $node): void
    {
        if (!in_array($node->name->toString(), $this->actingMethods)) {
            return;
        }

        $funcCall = ($node->args[0] ?? null)?->value;

        if (!$funcCall instanceof FuncCall || $funcCall->name->name !== $this->routeMethod) {
            return;
        }

        $argument = ($funcCall->args[0] ?? null)?->value;

        $route = match (true) {
            $argument instanceof String_  => $argument->value,
            $argument instanceof Variable => $this->auditor->getDeclaredVariable($argument->name),
            default                       => null,
        };

        if (empty($route)) {
            return;
        }

        $this->auditor->markRouteOccurrence($route);
    }
}
