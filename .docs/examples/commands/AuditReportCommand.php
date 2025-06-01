<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Examples\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use MyDev\AuditRoutes\Utilities\Cast;

class AuditReportCommand extends Command
{
    protected $name = 'Full audit report';
    protected $signature = 'route:audit-report';
    protected $description = 'Generate full audit report for Laravel routes';

    /** @var array<class-string<Command>, string> $reports */
    protected array $reports = [
        AdvancedReportingCommand::class => 'report.html',
        AuthenticatedCommand::class => 'auth.html',
        PhpUnitCoverageCommand::class => 'php-unit.html',
        PhpUnitDetailedCoverageCommand::class => 'php-unit-roles.html',
        ScopedBindingCommand::class => 'scoped-bindings.html',
    ];

    /**
     * @param Router $router
     * @return void
     */
    public function __construct(protected Router $router)
    {
        parent::__construct();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function handle(): void
    {
        $this->createIndex();

        foreach ($this->reports as $command => $filename) {
            $this->call($command, ['--filename' => $filename, '--export' => 'html']);
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function createIndex(): void
    {
        $template = Cast::string(Config::get('audit-routes.output.html-index-template'));

        if (empty($template)) {
            throw new Exception('Html output template has not been configured.');
        }

        $path = Cast::string(Config::get('audit-routes.output.directory'));

        if (!is_dir($path)) {
            mkdir($path, recursive: true);
        }

        $reports = [];
        foreach ($this->reports as $command => $filename) {
            $command = App::make($command);
            $name = Str::before($command->getDescription(), ' auditing for Laravel routes');

            $reports[$name] = $filename;
        }

        $fullPath = $path . DIRECTORY_SEPARATOR . 'index.html';
        file_put_contents($fullPath, View::make($template, ['reports' => $reports])->render());
    }
}
