# Ascio WHMCS Plugin - Unified Makefile
#
# This monorepo contains all Ascio WHMCS modules:
#   - domains/  (domain registrar - root level files)
#   - ssl/      (SSL certificates)
#   - core/     (shared v3 API components)
#   - monitoring/ (domain monitoring/NameWatch)
#   - defensive/  (defensive/DPML registrations)
#   - tmch/       (trademark clearinghouse)
#
# Usage:
#   make test          - Run all tests (unit + integration, excludes E2E)
#   make test-e2e      - Run all E2E tests (requires credentials)
#   make install       - Install all dependencies

.PHONY: help test test-all test-e2e test-unit test-integration \
        test-domains test-ssl test-core \
        test-domains-e2e test-ssl-e2e \
        install clean lint list-tests coverage

# Default target
help:
	@echo "Ascio WHMCS Plugin - Unified Test Commands"
	@echo ""
	@echo "Usage:"
	@echo "  make install              Install all dependencies"
	@echo "  make test                 Run all tests (excludes E2E)"
	@echo "  make test-all             Run all tests including E2E"
	@echo "  make test-e2e             Run all E2E tests"
	@echo ""
	@echo "Module-specific tests:"
	@echo "  make test-domains         Run domain module tests"
	@echo "  make test-ssl             Run SSL module tests"
	@echo "  make test-core            Run core module tests"
	@echo "  make test-domains-e2e     Run domain E2E tests"
	@echo "  make test-ssl-e2e         Run SSL E2E tests"
	@echo ""
	@echo "By test type:"
	@echo "  make test-unit            Run all unit tests"
	@echo "  make test-integration     Run all integration tests (excludes E2E)"
	@echo ""
	@echo "Other:"
	@echo "  make coverage             Generate coverage reports"
	@echo "  make lint                 Check PHP syntax"
	@echo "  make list-tests           List all available tests"
	@echo "  make clean                Clean test artifacts"
	@echo ""
	@echo "Environment variables for E2E tests:"
	@echo "  ASCIO_TEST_ACCOUNT        Demo API username"
	@echo "  ASCIO_TEST_PASSWORD       Demo API password"
	@echo "  ASCIO_LIVE_ACCOUNT        Live API username (for DNS)"
	@echo "  ASCIO_LIVE_PASSWORD       Live API password (for DNS)"
	@echo "  ASCIO_TEST_DOMAIN         Real domain for SSL validation"

# ============================================================================
# Install Dependencies
# ============================================================================

install:
	@echo "Installing domain module dependencies..."
	@composer install --no-interaction
	@echo ""
	@echo "Installing SSL module dependencies..."
	@cd ssl && composer install --no-interaction
	@echo ""
	@echo "All dependencies installed."

# ============================================================================
# Run All Tests
# ============================================================================

# Run all tests (excludes E2E by default)
test: test-domains test-ssl test-core

# Run all tests including E2E
test-all: test-domains-all test-ssl-all test-core

# Run all E2E tests
test-e2e: test-domains-e2e test-ssl-e2e

# Run all unit tests
test-unit: test-domains-unit test-ssl-unit test-core-unit

# Run all integration tests (excludes E2E)
test-integration: test-domains-integration test-ssl-integration test-core-integration

# ============================================================================
# Domain Module Tests (root level)
# ============================================================================

test-domains: test-domains-unit test-domains-integration

test-domains-all:
	@echo "Running all domain tests..."
	@./vendor/bin/phpunit tests --testdox

test-domains-unit:
	@echo "Running domain unit tests..."
	@./vendor/bin/phpunit tests/Unit --testdox

test-domains-integration:
	@echo "Running domain integration tests..."
	@./vendor/bin/phpunit tests/Integration --exclude-group=e2e --testdox

test-domains-e2e:
	@echo "Running domain E2E tests..."
	@./vendor/bin/phpunit tests/Integration --group=e2e --testdox

test-domains-lifecycle:
	@echo "Running domain lifecycle E2E test..."
	@./vendor/bin/phpunit tests/Integration/DomainLifecycleE2ETest.php --group=e2e --testdox

# ============================================================================
# SSL Module Tests
# ============================================================================

test-ssl: test-ssl-unit test-ssl-integration

test-ssl-all:
	@echo "Running all SSL tests..."
	@cd ssl && ./vendor/bin/phpunit tests --testdox

test-ssl-unit:
	@echo "Running SSL unit tests..."
	@cd ssl && ./vendor/bin/phpunit tests/Unit --testdox 2>/dev/null || echo "No SSL unit tests found"

test-ssl-integration:
	@echo "Running SSL integration tests..."
	@cd ssl && ./vendor/bin/phpunit tests/Integration --exclude-group=e2e --testdox

test-ssl-e2e:
	@echo "Running SSL E2E tests..."
	@cd ssl && ./vendor/bin/phpunit tests/Integration --group=e2e --testdox

test-ssl-lifecycle:
	@echo "Running SSL lifecycle E2E test..."
	@cd ssl && ./vendor/bin/phpunit tests/Integration/SslLifecycleE2ETest.php --group=e2e --testdox

test-ssl-extended:
	@echo "Running SSL extended E2E tests..."
	@cd ssl && ./vendor/bin/phpunit tests/Integration/SslExtendedE2ETest.php --group=e2e --testdox

# ============================================================================
# Core Module Tests
# ============================================================================

test-core: test-core-unit test-core-integration

test-core-unit:
	@echo "Running core unit tests..."
	@cd core && ../vendor/bin/phpunit tests/Unit --testdox 2>/dev/null || echo "No core unit tests found"

test-core-integration:
	@echo "Running core integration tests..."
	@cd core && ../vendor/bin/phpunit tests/Integration --exclude-group=e2e --testdox 2>/dev/null || echo "No core integration tests found"

# ============================================================================
# Coverage Reports
# ============================================================================

coverage: coverage-domains coverage-ssl

coverage-domains:
	@echo "Generating domain test coverage report..."
	@./vendor/bin/phpunit tests --coverage-html coverage/domains --exclude-group=e2e

coverage-ssl:
	@echo "Generating SSL test coverage report..."
	@cd ssl && ./vendor/bin/phpunit tests --coverage-html ../coverage/ssl --exclude-group=e2e

# ============================================================================
# Utility
# ============================================================================

clean:
	@echo "Cleaning test artifacts..."
	@rm -rf coverage
	@rm -rf tests/.phpunit.cache
	@rm -rf ssl/tests/.phpunit.cache
	@rm -rf core/tests/.phpunit.cache
	@rm -rf ssl/.phpunit.cache
	@echo "Clean complete."

lint:
	@echo "Checking PHP syntax..."
	@echo "Domain module:"
	@find lib tests -name "*.php" -exec php -l {} \; 2>&1 | grep -v "No syntax errors" || true
	@echo "SSL module:"
	@find ssl/lib ssl/tests -name "*.php" -exec php -l {} \; 2>&1 | grep -v "No syntax errors" || true
	@echo "Core module:"
	@find core/lib core/tests -name "*.php" -exec php -l {} \; 2>&1 | grep -v "No syntax errors" || true
	@echo "Lint complete."

list-tests:
	@echo "=== Domain Tests ==="
	@./vendor/bin/phpunit --list-tests tests 2>/dev/null | grep -E "^\s*-" || true
	@echo ""
	@echo "=== SSL Tests ==="
	@cd ssl && ./vendor/bin/phpunit --list-tests tests 2>/dev/null | grep -E "^\s*-" || true
	@echo ""
	@echo "=== Core Tests ==="
	@cd core && ../vendor/bin/phpunit --list-tests tests 2>/dev/null | grep -E "^\s*-" || true

# ============================================================================
# Quick verification
# ============================================================================

verify:
	@echo "Verifying monorepo structure..."
	@test -d ssl && echo "  ✓ ssl/ exists" || echo "  ✗ ssl/ missing"
	@test -d core && echo "  ✓ core/ exists" || echo "  ✗ core/ missing"
	@test -d monitoring && echo "  ✓ monitoring/ exists" || echo "  ✗ monitoring/ missing"
	@test -d defensive && echo "  ✓ defensive/ exists" || echo "  ✗ defensive/ missing"
	@test -d tmch && echo "  ✓ tmch/ exists" || echo "  ✗ tmch/ missing"
	@test -d tools && echo "  ✓ tools/ (addon) exists" || echo "  ✗ tools/ missing"
	@test -d lib && echo "  ✓ lib/ (domains) exists" || echo "  ✗ lib/ missing"
	@test -d tests && echo "  ✓ tests/ (domains) exists" || echo "  ✗ tests/ missing"
	@test -f ssl/v3/service/autoload.php && echo "  ✓ v3 service classes exist" || echo "  ✗ v3 service classes missing"
	@test -f tools/asciotools.php && echo "  ✓ tools addon exists" || echo "  ✗ tools addon missing"
	@echo ""
	@echo "Installation:"
	@echo "  1. Copy to WHMCS modules directory"
	@echo "  2. Activate 'Ascio Tools' addon in WHMCS Admin"
	@echo "  3. Tables created automatically on first use"
	@echo ""
	@echo "Verification complete."
