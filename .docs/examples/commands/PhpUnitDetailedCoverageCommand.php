<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Examples\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use MyDev\AuditRoutes\Aggregators\AverageScore;
use MyDev\AuditRoutes\Aggregators\ConditionedCumulative;
use MyDev\AuditRoutes\Aggregators\FailedPercentage;
use MyDev\AuditRoutes\Aggregators\SuccessPercentage;
use MyDev\AuditRoutes\Auditors\PhpUnitAuditor;
use MyDev\AuditRoutes\AuditRoutes;
use MyDev\AuditRoutes\Contracts\ExportInterface;
use MyDev\AuditRoutes\Entities\NodeAccessor;
use MyDev\AuditRoutes\Output\Export\ExportFactory;
use MyDev\AuditRoutes\Output\OutputFactory;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\ClassMethod;
use ReflectionException;

class PhpUnitDetailedCoverageCommand extends Command
{
    protected $signature = 'route:audit-php-unit-detailed-coverage {--benchmark=1} {--export=} {--filename=}';
    protected $description = 'Run PHPUnit auditing for Laravel routes';

    /**
     * @param Router $router
     * @return void
     */
    public function __construct(protected Router $router)
    {
        parent::__construct();
    }

    /**
     * @return int
     * @throws ReflectionException
     */
    public function handle(): int
    {
        $output = OutputFactory::channel($this->output)->setExporter($this->getExporter())->build();

        $auditors = array_map(
            fn (string $role): PhpUnitAuditor => PhpUnitAuditor::make([
                fn (ClassMethod $node): bool => (bool) (new NodeAccessor($node))
                    ->find(function (MethodCall $node): bool {
                        return strval($node->name) === 'actingAs';
                    })
                    ?->has(function (Node $node) use ($role): bool {
                        return str_contains(strtolower(strval($node->name)), $role);
                    }),
            ])->setName("PHPUnit coverage for acting as '{$role}'"),
            ['user', 'admin'],
        );

        $auditors[] = PhpUnitAuditor::make()->setName('PHPUnit coverage');

        $result = AuditRoutes::for($this->router->getRoutes()->getRoutes())
            ->setBenchmark((int) $this->option('benchmark'))
            ->run($auditors);

        return $output->generate($result)->value;
    }

    /** @return null | ExportInterface */
    protected function getExporter(): ?ExportInterface
    {
        return ExportFactory::channel($this->output)->build(
            $this->option('export'),
            $this->option('filename'),
        )?->setAggregators([
            new ConditionedCumulative('Total routes'),
            new FailedPercentage('Uncovered rate'),
            new SuccessPercentage('Covered rate'),
            new AverageScore('Average coverage'),
        ]);
    }
}
