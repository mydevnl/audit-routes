# CI/CD Integration Guide

Integrate route security auditing into your continuous integration and deployment pipelines. Automate security compliance checks, prevent insecure deployments, and maintain consistent security standards.

## Overview

CI/CD integration allows you to:
- **Block deployments** with security vulnerabilities
- **Generate automated reports** for security reviews
- **Track security metrics** over time
- **Enforce team standards** consistently
- **Catch regressions** before production

## GitHub Actions Integration

### Basic Security Gate

Create a workflow that blocks deployments for security violations:

```yaml
# .github/workflows/security-audit.yml
name: Security Audit

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  security-audit:
    runs-on: ubuntu-latest

    steps:
    - name: Checkout code
      uses: actions/checkout@v3

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        extensions: json, tokenizer, mbstring
        coverage: none

    - name: Cache Composer dependencies
      uses: actions/cache@v3
      with:
        path: vendor
        key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
        restore-keys: |
          ${{ runner.os }}-composer-

    - name: Install dependencies
      run: composer install --no-interaction --no-progress --dev

    - name: Run route security audit
      run: |
        php artisan route:audit --benchmark 50 --export json --filename security-audit.json
        echo "AUDIT_RESULT=$?" >> $GITHUB_ENV

    - name: Upload audit report
      if: always()
      uses: actions/upload-artifact@v3
      with:
        name: security-audit-report
        path: storage/exports/audit-routes/

    - name: Check audit results
      run: |
        if [ $AUDIT_RESULT -ne 0 ]; then
          echo "❌ Security audit failed - blocking deployment"
          jq -r '.routes[] | select(.score < 50) | "Route: \(.identifier) Score: \(.score)"' security-audit.json || echo "Failed routes detected"
          exit 1
        else
          echo "✅ Security audit passed"
        fi
```

### Advanced Workflow with Reporting

More sophisticated workflow with detailed reporting and notifications:

```yaml
# .github/workflows/comprehensive-security.yml
name: Comprehensive Security Analysis

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]
  schedule:
    - cron: '0 2 * * 1'  # Weekly on Monday at 2 AM

jobs:
  security-analysis:
    runs-on: ubuntu-latest

    strategy:
      matrix:
        audit-type: [comprehensive, auth, test-coverage]

    steps:
    - name: Checkout code
      uses: actions/checkout@v3

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        extensions: json, tokenizer, mbstring

    - name: Install dependencies
      run: composer install --dev --no-interaction

    - name: Run security audit
      id: audit
      run: |
        case "${{ matrix.audit-type }}" in
          "comprehensive")
            php artisan route:audit --benchmark 75 --export json --filename comprehensive.json
            ;;
          "auth")
            php artisan route:audit-auth --export json --filename auth.json
            ;;
          "test-coverage")
            php artisan route:audit-test-coverage --benchmark 1 --export json --filename tests.json
            ;;
        esac
        echo "audit-exit-code=$?" >> $GITHUB_OUTPUT

    - name: Generate HTML reports
      if: always()
      run: |
        case "${{ matrix.audit-type }}" in
          "comprehensive")
            php artisan route:audit-report
            ;;
          "auth")
            php artisan route:audit-auth --export html --filename auth-report.html
            ;;
          "test-coverage")
            php artisan route:audit-test-coverage --export html --filename coverage-report.html
            ;;
        esac

    - name: Parse audit results
      id: results
      if: always()
      run: |
        REPORT_FILE="storage/exports/audit-routes/${{ matrix.audit-type }}.json"
        if [ -f "$REPORT_FILE" ]; then
          TOTAL=$(jq '.summary.total_routes // 0' "$REPORT_FILE")
          FAILED=$(jq '.summary.failed // 0' "$REPORT_FILE")
          PASSED=$(jq '.summary.passed // 0' "$REPORT_FILE")

          echo "total-routes=$TOTAL" >> $GITHUB_OUTPUT
          echo "failed-routes=$FAILED" >> $GITHUB_OUTPUT
          echo "passed-routes=$PASSED" >> $GITHUB_OUTPUT
        else
          echo "total-routes=0" >> $GITHUB_OUTPUT
          echo "failed-routes=0" >> $GITHUB_OUTPUT
          echo "passed-routes=0" >> $GITHUB_OUTPUT
        fi

    - name: Upload artifacts
      if: always()
      uses: actions/upload-artifact@v3
      with:
        name: security-reports-${{ matrix.audit-type }}
        path: storage/exports/audit-routes/

    - name: Create PR comment
      if: github.event_name == 'pull_request' && matrix.audit-type == 'comprehensive'
      uses: actions/github-script@v6
      with:
        script: |
          const auditResults = {
            total: ${{ steps.results.outputs.total-routes }},
            failed: ${{ steps.results.outputs.failed-routes }},
            passed: ${{ steps.results.outputs.passed-routes }}
          };

          const comment = `## 🔒 Security Audit Results

          **${{ matrix.audit-type }}** audit completed:
          - ✅ **${auditResults.passed}** routes passed
          - ❌ **${auditResults.failed}** routes failed
          - 📊 **${auditResults.total}** total routes analyzed

          ${auditResults.failed > 0 ? '⚠️ **Action Required**: Fix failing routes before merge' : '🎉 All security checks passed!'}

          [View detailed report](https://github.com/${{ github.repository }}/actions/runs/${{ github.run_id }})`;

          github.rest.issues.createComment({
            issue_number: context.issue.number,
            owner: context.repo.owner,
            repo: context.repo.repo,
            body: comment
          });

    - name: Fail on security violations
      if: matrix.audit-type == 'comprehensive' && steps.results.outputs.failed-routes != '0'
      run: |
        echo "❌ Security audit failed: ${{ steps.results.outputs.failed-routes }} routes below security threshold"
        exit 1
```

## GitLab CI/CD Integration

### Basic Pipeline Configuration

```yaml
# .gitlab-ci.yml
stages:
  - test
  - security
  - deploy

variables:
  COMPOSER_CACHE_DIR: "$CI_PROJECT_DIR/.composer-cache"

cache:
  key: "$CI_COMMIT_REF_SLUG"
  paths:
    - vendor/
    - .composer-cache/

security-audit:
  stage: security
  image: php:8.2-cli
  before_script:
    - apt-get update -qq && apt-get install -y -qq git unzip
    - curl -sS https://getcomposer.org/installer | php
    - php composer.phar install --dev --no-interaction
  script:
    - php artisan route:audit --benchmark 50 --export json --filename gitlab-audit.json
  after_script:
    - |
      if [ -f "storage/exports/audit-routes/gitlab-audit.json" ]; then
        FAILED=$(jq '.summary.failed // 0' storage/exports/audit-routes/gitlab-audit.json)
        if [ "$FAILED" -gt 0 ]; then
          echo "Security audit failed: $FAILED routes below threshold"
          exit 1
        fi
      fi
  artifacts:
    when: always
    paths:
      - storage/exports/audit-routes/
    reports:
      junit: storage/exports/audit-routes/junit-report.xml
  only:
    - main
    - merge_requests
```

### Advanced GitLab Pipeline

```yaml
# .gitlab-ci.yml
include:
  - template: Security/Secret-Detection.gitlab-ci.yml

stages:
  - install
  - test
  - security-audit
  - security-report
  - deploy

variables:
  SECURITY_BENCHMARK: "75"

install-dependencies:
  stage: install
  image: composer:2
  script:
    - composer install --dev --optimize-autoloader --no-interaction
  artifacts:
    paths:
      - vendor/
    expire_in: 1 hour
  cache:
    key: composer-$CI_COMMIT_REF_SLUG
    paths:
      - vendor/

.security-audit-base: &security-audit-base
  stage: security-audit
  image: php:8.2-cli
  dependencies:
    - install-dependencies
  before_script:
    - php --version

comprehensive-security-audit:
  <<: *security-audit-base
  script:
    - php artisan route:audit --benchmark $SECURITY_BENCHMARK --export json --filename comprehensive.json
    - php artisan route:audit-report
  artifacts:
    when: always
    paths:
      - storage/exports/audit-routes/
    reports:
      junit: storage/exports/audit-routes/junit-report.xml
  after_script:
    - |
      FAILED=$(jq '.summary.failed // 0' storage/exports/audit-routes/comprehensive.json 2>/dev/null || echo "0")
      echo "Security audit completed: $FAILED routes failed"
      if [ "$FAILED" -gt 0 ]; then
        echo "❌ Deployment blocked due to security violations"
        exit 1
      fi

authentication-audit:
  <<: *security-audit-base
  script:
    - php artisan route:audit-auth --export json --filename auth-audit.json
  artifacts:
    when: always
    paths:
      - storage/exports/audit-routes/auth-audit.json
  allow_failure: true

test-coverage-audit:
  <<: *security-audit-base
  script:
    - php artisan route:audit-test-coverage --benchmark 1 --export json --filename coverage-audit.json
  artifacts:
    when: always
    paths:
      - storage/exports/audit-routes/coverage-audit.json
  allow_failure: true

security-report:
  stage: security-report
  image: alpine:latest
  dependencies:
    - comprehensive-security-audit
    - authentication-audit
    - test-coverage-audit
  before_script:
    - apk add --no-cache jq curl
  script:
    - |
      # Create summary report
      echo "# Security Audit Summary" > security-summary.md
      echo "**Pipeline:** $CI_PIPELINE_ID" >> security-summary.md
      echo "**Commit:** $CI_COMMIT_SHA" >> security-summary.md
      echo "" >> security-summary.md

      # Add comprehensive results
      if [ -f "storage/exports/audit-routes/comprehensive.json" ]; then
        TOTAL=$(jq '.summary.total_routes // 0' storage/exports/audit-routes/comprehensive.json)
        FAILED=$(jq '.summary.failed // 0' storage/exports/audit-routes/comprehensive.json)
        echo "**Comprehensive Audit:** $FAILED/$TOTAL routes failed" >> security-summary.md
      fi
    - |
      # Post to Slack if webhook is configured
      if [ -n "$SLACK_WEBHOOK" ]; then
        curl -X POST -H 'Content-type: application/json' \
          --data "{'text':'Security audit completed for $CI_PROJECT_NAME: $FAILED routes failed'}" \
          $SLACK_WEBHOOK
      fi
  artifacts:
    paths:
      - security-summary.md
  when: always
```

## Jenkins Pipeline Integration

### Declarative Pipeline

```groovy
// Jenkinsfile
pipeline {
    agent any

    environment {
        SECURITY_BENCHMARK = '50'
        COMPOSER_HOME = "${WORKSPACE}/.composer"
    }

    stages {
        stage('Preparation') {
            steps {
                checkout scm
                sh 'composer install --dev --no-interaction'
            }
        }

        stage('Security Audit') {
            parallel {
                stage('Comprehensive Audit') {
                    steps {
                        sh """
                            php artisan route:audit --benchmark \${SECURITY_BENCHMARK} \
                            --export json --filename comprehensive.json
                        """

                        script {
                            def auditResult = readJSON file: 'storage/exports/audit-routes/comprehensive.json'

                            if (auditResult.summary.failed > 0) {
                                currentBuild.result = 'UNSTABLE'
                                echo "Security audit failed: ${auditResult.summary.failed} routes below threshold"
                            }
                        }
                    }
                }

                stage('Authentication Audit') {
                    steps {
                        sh 'php artisan route:audit-auth --export json --filename auth.json'
                    }
                }

                stage('Test Coverage') {
                    steps {
                        sh 'php artisan route:audit-test-coverage --benchmark 1 --export json --filename coverage.json'
                    }
                }
            }
        }

        stage('Generate Reports') {
            steps {
                sh 'php artisan route:audit-report'

                publishHTML([
                    allowMissing: false,
                    alwaysLinkToLastBuild: true,
                    keepAll: true,
                    reportDir: 'storage/exports/audit-routes',
                    reportFiles: 'index.html',
                    reportName: 'Security Audit Report'
                ])
            }
        }
    }

    post {
        always {
            archiveArtifacts artifacts: 'storage/exports/audit-routes/**', fingerprint: true
        }

        failure {
            emailext (
                subject: "Security Audit Failed: ${env.JOB_NAME} - ${env.BUILD_NUMBER}",
                body: "Security audit failed for ${env.JOB_NAME}. Check the console output at ${env.BUILD_URL}",
                to: "${env.SECURITY_TEAM_EMAIL}"
            )
        }

        unstable {
            slackSend (
                color: 'warning',
                message: "Security audit warnings for ${env.JOB_NAME}: ${env.BUILD_URL}"
            )
        }
    }
}
```

## Docker Integration

### Multi-stage Build with Security Audit

```dockerfile
# Dockerfile.security
FROM php:8.2-cli as security-audit

# Install dependencies
RUN apt-get update && apt-get install -y git unzip curl jq
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install PHP extensions
RUN docker-php-ext-install json tokenizer

WORKDIR /app

# Copy application files
COPY . .

# Install Composer dependencies
RUN composer install --dev --no-interaction --optimize-autoloader

# Run security audit
RUN php artisan route:audit --benchmark 50 --export json --filename docker-audit.json

# Verify results
RUN FAILED=$(jq '.summary.failed // 0' storage/exports/audit-routes/docker-audit.json) && \
    if [ "$FAILED" -gt 0 ]; then \
        echo "Security audit failed: $FAILED routes below threshold" && \
        exit 1; \
    fi

# Production stage continues only if security audit passes
FROM php:8.2-fpm as production
# ... production configuration
```

### Docker Compose for Development

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    volumes:
      - .:/app
      - ./storage/exports:/app/storage/exports
    environment:
      - AUDIT_BENCHMARK=25

  security-audit:
    build:
      context: .
      dockerfile: Dockerfile.security
    volumes:
      - ./storage/exports:/app/storage/exports
    depends_on:
      - app
    command: >
      sh -c "
        php artisan route:audit --benchmark 25 &&
        php artisan route:audit-report
      "
```

## Team Integration Patterns

### Pre-commit Hooks

Prevent committing insecure routes:

```bash
#!/bin/bash
# .git/hooks/pre-commit

echo "Running security audit..."

# Run quick security check
php artisan route:audit --benchmark 25 --export json --filename precommit.json

FAILED=$(jq '.summary.failed // 0' storage/exports/audit-routes/precommit.json 2>/dev/null || echo "0")

if [ "$FAILED" -gt 0 ]; then
    echo "❌ Commit blocked: $FAILED routes fail security audit"
    echo "Run 'php artisan route:audit -vv' for details"
    exit 1
fi

echo "✅ Security audit passed"
exit 0
```

### Development Scripts

```bash
#!/bin/bash
# scripts/security-check.sh

set -e

echo "🔒 Running comprehensive security analysis..."

# Run all audit types
php artisan route:audit --benchmark 50 --export json --filename security-check.json
php artisan route:audit-auth --export json --filename auth-check.json
php artisan route:audit-test-coverage --benchmark 1 --export json --filename coverage-check.json

# Generate reports
php artisan route:audit-report

# Parse results
TOTAL=$(jq '.summary.total_routes // 0' storage/exports/audit-routes/security-check.json)
FAILED=$(jq '.summary.failed // 0' storage/exports/audit-routes/security-check.json)
PASSED=$((TOTAL - FAILED))

echo "📊 Security Analysis Results:"
echo "   Total routes: $TOTAL"
echo "   Passed: $PASSED"
echo "   Failed: $FAILED"

if [ "$FAILED" -gt 0 ]; then
    echo "❌ Security issues found. Review the report at:"
    echo "   $(pwd)/storage/exports/audit-routes/index.html"
    exit 1
else
    echo "✅ All security checks passed!"
fi
```

## Monitoring and Alerting

### Prometheus Metrics Integration

```php
// app/Console/Commands/SecurityMetricsCommand.php

class SecurityMetricsCommand extends Command
{
    protected $signature = 'security:metrics';

    public function handle()
    {
        $result = AuditRoutes::for($this->router->getRoutes())->run([
            PolicyAuditor::make(),
            PhpUnitAuditor::make(),
        ]);

        $metrics = [
            'audit_routes_total' => $result->count(),
            'audit_routes_passed' => $result->where('status', 'passed')->count(),
            'audit_routes_failed' => $result->where('status', 'failed')->count(),
            'audit_average_score' => $result->avg('score'),
        ];

        // Export to Prometheus
        foreach ($metrics as $metric => $value) {
            file_put_contents('/var/lib/prometheus/textfile_collector/laravel_security.prom',
                "# HELP {$metric} Laravel route security metric\n" .
                "# TYPE {$metric} gauge\n" .
                "{$metric} {$value}\n",
                FILE_APPEND | LOCK_EX
            );
        }
    }
}
```

### Slack Integration

```bash
#!/bin/bash
# scripts/slack-notify.sh

WEBHOOK_URL="${SLACK_WEBHOOK_URL}"
FAILED_ROUTES="$1"
TOTAL_ROUTES="$2"

if [ -n "$WEBHOOK_URL" ]; then
    curl -X POST -H 'Content-type: application/json' \
        --data "{
            'text': '🔒 Security Audit Complete',
            'attachments': [{
                'color': '$([ "$FAILED_ROUTES" -gt 0 ] && echo "danger" || echo "good")',
                'fields': [
                    {'title': 'Total Routes', 'value': '$TOTAL_ROUTES', 'short': true},
                    {'title': 'Failed Routes', 'value': '$FAILED_ROUTES', 'short': true},
                    {'title': 'Status', 'value': '$([ "$FAILED_ROUTES" -gt 0 ] && echo "Action Required" || echo "All Clear")'}
                ]
            }]
        }" \
        "$WEBHOOK_URL"
fi
```

## Best Practices

### Performance Optimization

- **Cache dependencies** between pipeline runs
- **Run focused audits** rather than comprehensive when possible
- **Parallelize** different audit types
- **Use appropriate timeouts** for large applications

### Security Considerations

- **Never expose audit results** in public artifacts
- **Secure webhook URLs** and API keys
- **Limit access** to security reports
- **Regular review** of security benchmarks

## Next Steps

- **[Testing Guide](testing.md)**: PHPUnit assertions and test suite integration
- **[Advanced Usage Guide](advanced-usage.md)**: Complex audit configurations
- **[Custom Auditors Guide](custom-auditors.md)**: Build specialized security checks
- **[Troubleshooting Guide](troubleshooting.md)**: Resolve CI/CD integration issues
- **[Real-world Examples](../reference/examples/real-world.md)**: Production use cases and patterns