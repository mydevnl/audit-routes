<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Auditors;

use Illuminate\Support\Facades\Config;
use MyDev\AuditRoutes\Routes\RouteInterface;
use MyDev\AuditRoutes\Traits\Auditable;
use MyDev\AuditRoutes\Traits\TracksRouteOccurrences;
use MyDev\AuditRoutes\Traits\TracksVariabeles;
use MyDev\AuditRoutes\Traversers\CoversRouteAttributeTraverser;
use MyDev\AuditRoutes\Traversers\RouteTestTraverser;
use MyDev\AuditRoutes\Utilities\ClassDiscovery;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;

class TestAuditor implements AuditorInterface
{
    use Auditable;
    use TracksVariabeles;
    use TracksRouteOccurrences;

    /** @var Parser $parser */
    private Parser $parser;

    /** @var NodeTraverser $traverser */
    private NodeTraverser $traverser;

    /** @return void */
    public function __construct()
    {
        $this->setUpParser();

        $testImplementation = Config::get('audit-routes.tests.implementation');
        $testDirectory = Config::get('audit-routes.tests.directory');

        foreach (ClassDiscovery::subclassesOf($testImplementation, $testDirectory) as $testClass) {
            $syntaxTree = $this->parser->parse(ClassDiscovery::source($testClass));
            $this->traverser->traverse($syntaxTree);
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

    /** @return void */
    protected function setUpParser(): void
    {
        $this->parser = (new ParserFactory())->createForNewestSupportedVersion();

        $this->traverser = new NodeTraverser(
            new RouteTestTraverser($this),
            new CoversRouteAttributeTraverser($this),
        );
    }
}
