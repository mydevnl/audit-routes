# System Architecture Overview

Understanding Audit Routes' internal architecture helps you extend functionality, debug issues, and contribute to the project. This guide covers the core components based on the actual implementation.

## High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         AuditRoutes Core                        │
├──────────────────────┬───────────────────┬──────────────────────┤
│   Route              │   Auditor         │   Output             │
│   Factory            │   Factory         │   Export             │
└──────────────────────┴───────────────────┴──────────────────────┘
            │                    │                    │
            ▼                    ▼                    ▼
┌────────────────────┐  ┌─────────────────┐  ┌────────────────────┐
│   RouteInterface   │  │   AST Visitors  │  │   Export           │
│   Implementations  │  │   & Actions     │  │   Implementations  │
└────────────────────┘  └─────────────────┘  └────────────────────┘
```

## Core Components

### AuditRoutes Engine

**Location**: `src/AuditRoutes.php`

The central orchestrator that coordinates all auditing operations. Uses the `IgnoresRoutes` trait for route filtering and manages the complete audit workflow.

**Key Responsibilities**:
- Route collection management through `RouteFactory::collection()`
- Configuration loading from `audit-routes` config
- Auditor initialization via `AuditorFactory::buildMany()`
- Individual route auditing via `AuditedRoute::audit()`

**Example Usage**:
```php
$result = AuditRoutes::for($routes)
    ->setBenchmark(50)
    ->run([
        PolicyAuditor::make()->setWeight(30),
        MiddlewareAuditor::make(['auth'])->setWeight(25)
    ]);
```

### Route System

**Core Interface**: `src/Contracts/RouteInterface.php`

Abstracts different route implementations for consistent auditor interaction:

```php
interface RouteInterface
{
    public function getName(): ?string;
    public function getUri(): string;
    public function getIdentifier(): string;
    public function getMiddlewares(): array; // Returns array<int, Middleware>
    public function hasMiddleware(string $middleware): bool;
    public function getClass(): string;
    public function hasScopedBindings(): ?bool;
}
```

**Key Components**:
- `RouteFactory`: Creates RouteInterface implementations from Laravel routes
- `Middleware` entity: Represents middleware with attribute parsing
- Route validation through `IgnoresRoutes` trait

### Auditor System

**Core Interface**: `src/Contracts/AuditorInterface.php`

Comprehensive interface supporting factory methods, execution, scoring, filtering, and identification:

**Key Methods**:
- `make(?array $arguments = null)`: Static factory method
- `run(RouteInterface $route)`: Main execution with validation
- `handle(RouteInterface $route)`: Core auditor logic
- `when(Closure $condition)`: Conditional execution
- `ignoreRoutes(array $routes)`: Route filtering
- Scoring: `setWeight()`, `setPenalty()`, `setLimit()`

**Auditable Trait**: `src/Traits/Auditable.php`

Combines multiple traits for complete auditor functionality:
- `ConditionalAuditable`: Handles `when()` conditions
- `IgnoresRoutes`: Handles route filtering
- `Nameable`: Handles auditor identification
- Reflection-based validation for custom `validate*` methods

**Built-in Auditors**:
- `PhpUnitAuditor`: Test coverage analysis using AST parsing
- `PolicyAuditor`: Laravel policy middleware detection (2+ attributes)
- `PermissionAuditor`: Permission middleware detection (1 attribute)
- `MiddlewareAuditor`: Generic middleware validation
- `ScopedBindingAuditor`: Route model binding security

### AuditedRoute System

**Location**: `src/Entities/AuditedRoute.php`

Handles individual route auditing and score calculation:

**Process Flow**:
```php
AuditedRoute::for($route, $benchmark)
    ->run($auditors) // Runs each auditor, accumulates scores
    ->getStatus()    // Determines Passed/Failed
```

**Status Categories**:
- **Passed**: Score >= benchmark
- **Failed**: Score < benchmark but >= 0

### Factory System

**AuditorFactory**: `src/Auditors/AuditorFactory.php`
- Handles class-string => weight arrays and AuditorInterface instances
- Uses `buildMany()` to initialize multiple auditors
- Supports mixed input formats

**RouteFactory**: `src/Routes/RouteFactory.php`
- Converts Laravel routes to RouteInterface implementations
- Manages route collection creation from iterables

### Export System

**Core Interface**: `src/Contracts/ExportInterface.php`
```php
interface ExportInterface extends OutputInterface
{
    public function setAggregators(array $aggregators): self;
    public function setFilename(?string $filename): self;
}
```

**Implementations**:
- `HtmlExport`: Generates styled HTML reports
- `JsonExport`: Outputs machine-readable JSON

## Data Flow Architecture

### Processing Pipeline

```
User Command (route:audit)
        ↓
Command Handler
        ↓
new AuditRoutes($routes)
├── RouteFactory::collection($routes) → Array<RouteInterface>
├── Config::get('audit-routes.benchmark') → int $benchmark
└── Config::get('audit-routes.ignored-routes') → Default ignored routes
        ↓
AuditRoutes::run($auditors)
├── AuditorFactory::buildMany($auditors) → Array<AuditorInterface>
├── foreach route: $this->validateRoute($route) → bool
└── AuditedRoute::for($route, $benchmark)->audit($auditors)
        ↓
AuditedRoute::audit($auditors)
├── foreach auditor: $auditor->run($route)
│   ├── $auditor->validate($route) → Check conditions/filters
│   └── $auditor->handle($route) → Execute auditor logic
├── $auditor->getScore($rawScore) → Apply weight/penalty/limit
└── new AuditorResult($auditor, $score)
        ↓
AuditedRouteCollection
├── Calculate total scores
├── Determine pass/fail status
└── Generate output via Export classes
```

### Scoring System

**Score Calculation** (in `Auditable::getScore()`):
- Zero scores return the penalty value
- Positive scores are multiplied by weight and capped by limit
- Formula: `min($limit, $score * $weight)` for positive scores

**Examples**:
```php
$auditor = PolicyAuditor::make()->setWeight(25)->setPenalty(-10);
$auditor->getScore(0); // Returns -10 (penalty)
$auditor->getScore(2); // Returns 50 (2 * 25)
```

## AST Analysis System

**Key Components**:
- `CollectTestingMethods`: Action class that discovers test files and methods
- `PhpUnitMethodVisitor`: AST visitor that extracts route calls from test methods
- `StringValueVisitor` & `VariableValueVisitor`: Extract literals and variables
- `CallbackVisitor`: Generic AST traversal utility

**PhpUnitAuditor Integration**:
- Implements multiple interfaces: `VariableTrackerInterface`, `RouteOccurrenceTrackerInterface`
- Uses traits: `TracksVariables`, `TracksRouteOccurrences`
- Configurable test detection with `testConditions` array
- Caches parsed results with `isParsed` flag

## Configuration Architecture

**Configuration Loading**:
- Default package configuration
- `config/audit-routes.php` overrides
- Environment variable overrides
- Runtime method call overrides

**Hierarchical Priority**:
```php
// Runtime > Environment > Config > Defaults
$this->benchmark = Cast::int(Config::get('audit-routes.benchmark'));
$ignoredRoutes = Cast::array(Config::get('audit-routes.ignored-routes'));
```

## Extension Points

### Custom Auditor Development

**Basic Pattern**:
```php
class CustomAuditor implements AuditorInterface
{
    use Auditable;

    public function handle(RouteInterface $route): int
    {
        // Your analysis logic
        return $this->analyzeRoute($route) ? 1 : 0;
    }

    // Optional: Custom validation (called automatically via reflection)
    protected function validateCustomCondition(RouteInterface $route): bool
    {
        return $route->getUri() !== '/unsafe';
    }
}
```

### Custom Export Format

**Basic Pattern**:
```php
class CustomExport implements ExportInterface
{
    public function setAggregators(array $aggregators): self { /* */ }
    public function setFilename(?string $filename): self { /* */ }

    // From OutputInterface
    public function render(AuditedRouteCollection $collection): string
    {
        return $this->generateCustomOutput($collection);
    }
}
```

## Performance Considerations

### Memory Management
- Array-based route storage for small-medium collections
- Per-auditor caching in AST parsing
- Lazy evaluation in auditor conditions
- Result object reuse

### AST Parsing Optimization
- `CollectTestingMethods` caches parsed test files
- Reflection-based validation reduces redundant checks
- Visitor pattern minimizes AST traversals

### Caching Strategies
- Directory-level caching for test discovery
- Parse-once strategy with boolean flags
- Result sharing across auditor instances

## Testing Architecture

**Structure**:
- Unit tests for individual auditors: `tests/Unit/Auditors/`
- Mock utilities in base test classes

**Test Utilities**:
```php
protected function createMockRoute(string $name, array $middlewares = []): RouteInterface
{
    // Returns properly configured RouteInterface mock
}
```

## Next Steps

- **[Auditor System Details](auditor-system.md)**: Deep dive into auditor architecture
- **[Custom Auditors Guide](../guides/custom-auditors.md)**: Practical auditor development
- **[Testing Guide](../guides/testing.md)**: PHPUnit assertions and test integration
- **[Contributing Guide](../../CONTRIBUTING.md)**: Development workflow and standards