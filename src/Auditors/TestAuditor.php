<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Auditors;

use Illuminate\Support\Facades\Config;
use MyDev\AuditRoutes\Routes\RouteInterface;
use MyDev\AuditRoutes\Traits\Auditable;
use MyDev\AuditRoutes\Traits\TracksRouteOccurrences;
use MyDev\AuditRoutes\Traits\TracksVariables;
use MyDev\AuditRoutes\Traversers\CoversRouteAttributeTraverser;
use MyDev\AuditRoutes\Traversers\RouteTestTraverser;
use MyDev\AuditRoutes\Utilities\ClassDiscovery;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

class TestAuditor implements AuditorInterface
{
    use Auditable;
    use TracksVariables;
    use TracksRouteOccurrences;

    /** @return void */
    public function __construct()
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();

        $traverser = new NodeTraverser(
            new RouteTestTraverser($this),
            new CoversRouteAttributeTraverser($this),
        );

        $testClasses = ClassDiscovery::subclassesOf(
            Config::get('audit-routes.tests.implementation'),
            Config::get('audit-routes.tests.directory'),
        );

        foreach ($testClasses as $testClass) {
            $syntaxTree = $parser->parse(ClassDiscovery::source($testClass));
            $traverser->traverse($syntaxTree);
        }
    }

    /**
     * @param RouteInterface $route
     * @return int
     */
    public function handle(RouteInterface $route): int
    {
        return $this->getScore($this->getRouteOccurrence($route->getIdentifier()));
    }
}
