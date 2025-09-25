# Contributing to Audit Routes

Thank you for your interest in contributing to Audit Routes! This guide will help you get started with development, testing, and submitting contributions.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Architecture Overview](#architecture-overview)
- [Making Changes](#making-changes)
- [Code Style](#code-style)
- [Release Process](#release-process)

## Code of Conduct

We are committed to providing a welcoming and inclusive experience for all contributors. Please read and follow our Code of Conduct:

- **Be respectful**: Treat all community members with respect and kindness
- **Be inclusive**: Welcome newcomers and diverse perspectives
- **Be constructive**: Provide helpful feedback and focus on solutions
- **Be professional**: Keep discussions focused on the project and technical topics

## Getting Started

### Prerequisites

- **PHP 8.0+** with required extensions
- **Composer** for dependency management
- **Laravel 9.0+** for testing integration
- **Git** for version control
- **PHPUnit** for testing

### Fork and Clone

1. **Fork the repository** on GitHub
2. **Clone your fork** locally:
   ```bash
   git clone https://github.com/YOUR_USERNAME/audit-routes.git
   cd audit-routes
   ```
3. **Add upstream remote**:
   ```bash
   git remote add upstream https://github.com/mydevnl/audit-routes.git
   ```

## Development Setup

### Install Dependencies

```bash
# Install package dependencies
composer install

# Install development dependencies
composer install --dev
```

### Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Generate application key (for test Laravel app)
php artisan key:generate
```

### Verify Installation

```bash
# Run tests to ensure everything works
composer test
```

## Architecture Overview

Understanding the package architecture helps you make effective contributions:

### Core Components

```
src/
├── Auditors/              # Security analysis implementations
│   ├── PhpUnitAuditor.php    # Test coverage analysis
│   ├── PolicyAuditor.php     # Laravel policy detection
│   ├── PermissionAuditor.php # Permission-based authorization
│   ├── MiddlewareAuditor.php # Middleware validation
│   └── ScopedBindingAuditor.php # Route model binding security
├── Commands/              # Artisan command implementations
│   ├── RouteAuditCommand.php       # Main audit command
│   ├── RouteAuditAuthCommand.php   # Authentication analysis
│   ├── RouteAuditReportCommand.php # HTML report generation
│   └── RouteAuditTestCoverageCommand.php # Test coverage analysis
├── Contracts/             # Interfaces and contracts
│   ├── AuditorInterface.php        # Auditor contract
│   ├── RouteInterface.php          # Route abstraction
│   └── ExporterInterface.php       # Report export contract
├── Routes/                # Route implementations
│   ├── LaravelRoute.php            # Laravel route adapter
│   └── SymfonyRoute.php            # Symfony route adapter (future)
├── Exporters/            # Report generation
│   ├── HtmlExporter.php            # HTML report generation
│   └── JsonExporter.php            # JSON export
├── Parsers/              # Code analysis
│   └── PhpUnitParser.php          # AST-based test parsing
└── Traits/               # Shared functionality
    ├── Auditable.php              # Common auditor methods
    └── Configurable.php           # Configuration handling
```

### Key Concepts

- **Auditors**: Analyze routes for specific security aspects and return scores
- **Routes**: Abstract route representations that work across frameworks
- **Exporters**: Generate reports in various formats (HTML, JSON)
- **Parsers**: Extract information from code using AST analysis

## Making Changes

### Before You Start

1. **Check existing issues**: Look for related issues or feature requests
2. **Create an issue**: Discuss major changes before implementing
3. **Create a branch**: Use descriptive branch names
   ```bash
   git checkout -b feature/add-csrf-auditor
   git checkout -b fix/policy-detection-bug
   git checkout -b docs/improve-installation-guide
   ```

### Types of Contributions

#### Bug Fixes

- **Small fixes**: Can be submitted directly as PRs
- **Complex bugs**: Create an issue first to discuss the approach
- **Include tests**: Demonstrate the bug and verify the fix
- **Update documentation**: If behavior changes

#### New Features

- **Create an issue**: Discuss the feature before implementation
- **Follow existing patterns**: Use similar implementations as references
- **Add comprehensive tests**: Cover all scenarios and edge cases
- **Update documentation**: Add usage examples and API docs

#### Documentation Improvements

- **Fix typos and errors**: Small changes can be submitted directly
- **Add examples**: Real-world usage examples are always welcome
- **Improve clarity**: Make complex concepts easier to understand
- **Add translations**: Help make docs accessible to more users

#### Performance Improvements

- **Benchmark before/after**: Demonstrate performance gains
- **Maintain compatibility**: Don't break existing APIs
- **Add tests**: Ensure functionality remains correct
- **Document changes**: Explain what was optimized and why

## Code Style

### PHP Standards

We follow **PSR-12** coding standards with some additional rules:

### Naming Conventions

- **Classes**: `PascalCase` (e.g., `PolicyAuditor`)
- **Methods**: `camelCase` (e.g., `handleRoute`)
- **Variables**: `camelCase` (e.g., `$routeScore`)
- **Constants**: `SCREAMING_SNAKE_CASE` (e.g., `MAX_SCORE`)

### Documentation Standards

- **DocBlocks**: Required for all public methods
- **Type hints**: Use for all parameters and return types
- **Comments**: Explain complex logic, not obvious code

### Pull Request Template

Use this template for your PR description:

```markdown
## Description
Brief description of changes and motivation.

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Testing
- [ ] Tests pass locally
- [ ] Added tests for new functionality
- [ ] Updated documentation

## Checklist
- [ ] Code follows project style guidelines
- [ ] Self-review of changes completed
- [ ] Comments added for complex logic
- [ ] Documentation updated
- [ ] No breaking changes without discussion
```

### Review Process

1. **Automated checks**: CI runs tests and static analysis
2. **Code review**: Maintainers review code quality and design
3. **Testing**: Verify functionality works as expected
4. **Documentation**: Ensure docs are updated appropriately
5. **Merge**: Once approved, changes are merged

### Review Guidelines

**For contributors**:
- **Be responsive**: Address feedback promptly
- **Ask questions**: If feedback is unclear, ask for clarification
- **Stay focused**: Keep discussions technical and constructive
- **Be patient**: Reviews take time, especially for complex changes

**For reviewers**:
- **Be thorough**: Check code quality, tests, and documentation
- **Be constructive**: Provide specific, actionable feedback
- **Be respectful**: Focus on code, not the person
- **Be timely**: Review PRs within reasonable timeframes

## Release Process

### Versioning

We follow **Semantic Versioning** (semver.org):

- **MAJOR** (1.0.0 → 2.0.0): Breaking changes
- **MINOR** (1.0.0 → 1.1.0): New features, backward compatible
- **PATCH** (1.0.1 → 1.0.2): Bug fixes, backward compatible

### Release Checklist

**For maintainers**:

1. **Update changelog** with all changes
2. **Update version** in relevant files
3. **Run full test suite** with all supported versions
4. **Create release tag** with detailed notes
5. **Publish to Packagist** (automatic via webhook)
6. **Update documentation** for new features
7. **Announce release** in relevant channels

## Getting Help

### Communication Channels

- **GitHub Issues**: Bug reports, feature requests
- **GitHub Discussions**: Questions, ideas, general discussion
- **Documentation**: Comprehensive guides and API reference

### Before Asking for Help

1. **Search existing issues** and discussions
2. **Check documentation** for answers
3. **Provide context**: Include Laravel version, package version, error messages
4. **Create minimal reproduction**: Isolate the problem

### Reporting Issues

**Good issue template**:
```markdown
**Bug Description**: Clear description of the problem

**To Reproduce**: Steps to reproduce the behavior
1. Configure auditor with ...
2. Run command ...
3. See error

**Expected Behavior**: What you expected to happen

**Environment**:
- Laravel Version: 10.x
- Package Version: 2.x
- PHP Version: 8.1

**Additional Context**: Any other relevant information
```

## Recognition

Contributors are, or will be, recognized in:
- **CHANGELOG.md**: Feature credits and bug fix acknowledgments
- **README.md**: Contributor list and acknowledgments
- **GitHub**: Contributor graph and commit history

Thank you for contributing to Audit Routes! Your help makes Laravel applications more secure. 🔒✨