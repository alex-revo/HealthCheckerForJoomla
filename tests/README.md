# Health Checker for Joomla - Unit Tests

This directory contains comprehensive unit tests for the Health Checker for Joomla extension.

## Overview

**Current Stats**: 1634 tests, 3023 assertions across 163 test files

The test suite provides thorough coverage of:
- Core interfaces and abstract classes
- Health check results and status enums
- Event system (CollectProvidersEvent, CollectCategoriesEvent, CollectChecksEvent, etc.)
- Provider and category metadata
- Registry services
- All 130+ health check implementations
- Controllers, models, and views
- Module dispatcher and helpers
- Plugin extensions (Core, Akeeba Backup, Akeeba Admin Tools, mySites.guru, Example)

## Requirements

- PHP 8.1 or later
- PHPUnit 10.x or later
- Composer dependencies installed

## Installation

Install PHPUnit and dependencies:

```bash
composer install
```

## Running Tests

### Run All Tests

```bash
vendor/bin/phpunit
```

### Run Specific Directory

```bash
# Component tests only
vendor/bin/phpunit tests/Unit/Component

# Plugin tests only
vendor/bin/phpunit tests/Unit/Plugin

# Module tests only
vendor/bin/phpunit tests/Unit/Module

# Specific category of checks
vendor/bin/phpunit tests/Unit/Plugin/Core/Checks/Security
```

### Run Single Test File

```bash
vendor/bin/phpunit tests/Unit/Component/Check/HealthStatusTest.php
```

### Run with Code Coverage

```bash
vendor/bin/phpunit --coverage-html coverage/
```

Then open `coverage/index.html` in your browser.

### Run with Specific Filter

```bash
# Run only tests matching a pattern
vendor/bin/phpunit --filter testGetSlugReturnsCorrectValue
```

## Test Structure

```
tests/
├── bootstrap.php                          # PHPUnit bootstrap file
├── stubs/                                 # Joomla framework stubs for testing
├── Utilities/                             # Test utilities and mocks
│   ├── JoomlaTextMock.php                # Mock for Joomla\CMS\Language\Text
│   └── MockFactory.php                   # Factory for creating test fixtures
└── Unit/                                  # Unit tests (163 test files)
    ├── ManifestXmlValidationTest.php     # XML manifest validation
    ├── Component/                         # Component tests
    │   ├── Category/                      # Category classes
    │   │   └── HealthCategoryTest.php
    │   ├── Check/                         # Health check core classes
    │   │   ├── AbstractHealthCheckTest.php
    │   │   ├── HealthCheckResultTest.php
    │   │   └── HealthStatusTest.php
    │   ├── Controller/                    # Controller tests
    │   │   ├── AjaxControllerTest.php
    │   │   └── DisplayControllerTest.php
    │   ├── Event/                         # Event classes
    │   │   ├── BeforeReportDisplayEventTest.php
    │   │   ├── CollectCategoriesEventTest.php
    │   │   ├── CollectChecksEventTest.php
    │   │   ├── CollectProvidersEventTest.php
    │   │   └── HealthCheckerEventsTest.php
    │   ├── Model/                         # Model tests
    │   ├── Provider/                      # Provider classes
    │   │   ├── ProviderMetadataTest.php
    │   │   └── ProviderRegistryTest.php
    │   ├── Service/                       # Service classes
    │   │   ├── CategoryRegistryTest.php
    │   │   └── HealthCheckRunnerTest.php
    │   └── View/Report/                   # View tests
    ├── Module/                            # Module tests
    │   ├── Dispatcher/                    # Dispatcher tests
    │   └── Helper/                        # Helper tests
    └── Plugin/                            # Plugin tests
        ├── AkeebaAdminTools/              # Admin Tools plugin tests
        │   ├── Categories/
        │   └── Extension/
        ├── AkeebaBackup/                  # Backup plugin tests
        │   └── Extension/
        ├── Core/                          # Core plugin tests (100+ check tests)
        │   └── Checks/                    # Organized by category
        │       ├── Content/               # Content quality checks
        │       ├── Database/              # Database checks
        │       ├── Extensions/            # Extension checks
        │       ├── Performance/           # Performance checks
        │       ├── Security/              # Security checks
        │       ├── Seo/                   # SEO checks
        │       ├── System/                # System checks
        │       └── Users/                 # User checks
        ├── Example/                       # Example plugin tests
        │   ├── Checks/
        │   └── Extension/
        └── MySitesGuru/                   # mySites.guru plugin tests
            ├── Checks/
            └── Extension/
```

## Writing New Tests

### Test Naming Convention

- Test files: `{ClassName}Test.php`
- Test methods: `test{MethodName}{Scenario}()`
- Examples:
  - `testGetSlugReturnsCorrectValue()`
  - `testConstructorSetsAllProperties()`
  - `testThrowsExceptionWhenAddingInvalidType()`

### Test Class Template

```php
<?php

declare(strict_types=1);

namespace HealthChecker\Tests\Unit\Component\Check;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(YourClass::class)]
class YourClassTest extends TestCase
{
    public function testSomething(): void
    {
        // Arrange
        $instance = new YourClass();

        // Act
        $result = $instance->someMethod();

        // Assert
        $this->assertSame('expected', $result);
    }
}
```

### Using Mock Factory

```php
use HealthChecker\Tests\Utilities\MockFactory;

// Create mock result
$result = MockFactory::createResult(
    status: HealthStatus::Good,
    title: 'Test Check',
    description: 'Test description'
);

// Create mock category
$category = MockFactory::createCategory(
    slug: 'test',
    label: 'Test Category',
    icon: 'fa-test'
);

// Create mock provider
$provider = MockFactory::createProvider(
    slug: 'test_plugin',
    name: 'Test Plugin'
);
```

## Code Coverage Goals

- **Minimum**: 80% line coverage
- **Target**: 90%+ line coverage
- **Critical Classes**: 100% coverage for:
  - HealthStatus
  - HealthCheckResult
  - AbstractHealthCheck
  - ProviderRegistry
  - CategoryRegistry
  - HealthCheckRunner
  - All health check implementations

## Testing Best Practices

1. **Test One Thing**: Each test should verify a single behavior
2. **Arrange-Act-Assert**: Structure tests clearly
3. **Descriptive Names**: Test names should describe what they test
4. **No Logic in Tests**: Tests should be straightforward, no conditionals
5. **Independent Tests**: Tests should not depend on each other
6. **Fast Tests**: Unit tests should run quickly (no database, no I/O)

## Mocking Guidelines

- **Use Stubs for Dependencies**: When testing a class that depends on others
- **Don't Mock Value Objects**: Test them directly
- **Mock Joomla APIs**: Use provided mocks (JoomlaTextMock, etc.)

### Example: Testing with Dependencies

```php
public function testSetDatabaseInjectsDatabase(): void
{
    $check = new SomeHealthCheck();
    $db = $this->createStub(DatabaseInterface::class);

    $check->setDatabase($db);

    $this->assertSame($db, $check->getDatabase());
}
```

## Continuous Integration

Tests run automatically on GitHub Actions for PHP 8.1-8.5:

```yaml
# GitHub Actions example
- name: Run PHPUnit Tests
  run: vendor/bin/phpunit --coverage-clover coverage.xml
```

See `.github/workflows/` for CI configuration details.

## Troubleshooting

### Tests Fail with "_JEXEC not defined"

The bootstrap file should handle this, but if you see errors:
- Ensure `phpunit.xml` points to `tests/bootstrap.php`
- Check that `_JEXEC` is defined in bootstrap.php

### Mock Text::_() Returns Wrong Values

Our JoomlaTextMock returns language keys unchanged. This is intentional for unit tests.

### Cannot Find Class

- Run `composer dump-autoload`
- Check namespace in your test matches the namespace in phpunit.xml
- Verify the class is in the correct directory

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [PHPUnit Assertions](https://phpunit.de/manual/current/en/appendixes.assertions.html)
- [Testing Best Practices](https://phpunit.de/manual/current/en/writing-tests-for-phpunit.html)

## Contributing

When adding new features:

1. Write tests first (TDD)
2. Ensure all tests pass
3. Maintain or improve code coverage
4. Follow existing test patterns
5. Add tests to appropriate test suite

## License

Same as Health Checker for Joomla - GPL v2 or later.
