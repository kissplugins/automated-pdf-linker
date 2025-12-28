# Neochrome WP Toolkit - Quick Start

**Version:** 1.0.29
© Copyright 2025 Neochrome, Inc.

> **For AI Assistants:** Read this file first to understand available tools.

---

## What's Included

| File | Purpose |
|------|---------|
| `bin/check-performance.sh` | Main script - scans PHP for performance antipatterns |
| `tests/fixtures/antipatterns.php` | Examples of bad patterns (for testing/reference) |
| `tests/fixtures/clean-code.php` | Examples of correct patterns |
| `tests/run-fixture-tests.sh` | Test runner for fixture validation |

---

## Quick Start

### Option A: Using Composer (Recommended)

If your project has the toolkit's `composer.json` or you've added the scripts to your own:

```bash
# Run performance audit
composer audit

# Run in strict mode (fails on warnings - for CI)
composer audit:strict

# Run full CI pipeline (audit + tests)
composer ci

# Verbose output (show all matches)
composer audit:verbose

# Scan specific directory
composer audit:src
```

### Option B: Direct Script Execution

```bash
# From project root (adjust path as needed)
./dist/bin/check-performance.sh --paths "."

# Scan specific folders
./dist/bin/check-performance.sh --paths "includes/ src/"

# Verbose output (show all matches)
./dist/bin/check-performance.sh --paths "." --verbose

# Strict mode (fail on warnings too)
./dist/bin/check-performance.sh --paths "." --strict

# Without log file
./dist/bin/check-performance.sh --paths "." --no-log
```

---

## Composer Scripts Reference

Add these to your project's `composer.json` for easy access:

```json
{
  "scripts": {
    "audit": "./dist/bin/check-performance.sh --paths '.'",
    "audit:verbose": "./dist/bin/check-performance.sh --paths '.' --verbose",
    "audit:strict": "./dist/bin/check-performance.sh --paths '.' --strict",
    "audit:src": "./dist/bin/check-performance.sh --paths 'src/'",
    "test": "./dist/tests/run-fixture-tests.sh",
    "ci": ["@audit:strict", "@test"]
  }
}
```

| Script | Description |
|--------|-------------|
| `composer audit` | Scan entire project for antipatterns |
| `composer audit:strict` | Strict mode - fail on warnings (for CI/CD) |
| `composer audit:verbose` | Show all matches, not just first occurrence |
| `composer audit:src` | Scan only `src/` directory |
| `composer test` | Run fixture validation tests |
| `composer ci` | Full CI pipeline: strict audit + tests |

---

## CI/CD Integration

### GitHub Actions

Add to your workflow (`.github/workflows/quality.yml`):

```yaml
name: Code Quality

on: [push, pull_request]

jobs:
  performance-audit:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Run Performance Audit
        run: composer ci
        # Or without Composer:
        # run: ./dist/bin/check-performance.sh --paths '.' --strict
```

### GitLab CI

```yaml
performance-audit:
  script:
    - composer ci
  only:
    - merge_requests
    - main
```

---

## Review Results

The script outputs:
- **ERRORS** `[CRITICAL]` `[HIGH]` - Must fix, will fail CI
- **WARNINGS** `[MEDIUM]` `[LOW]` - Review recommended

### Exit Codes

| Code | Meaning |
|------|---------|
| `0` | All checks passed |
| `1` | Errors found (critical issues) |
| `1` | Warnings found (when `--strict` mode) |

---

## Logging

Logs are written to `dist/logs/` by default:
- **Filename format:** `YYYY-MM-DD-HHMMSS-UTC.log`
- **Disable logging:** Use `--no-log` flag

Add to `.gitignore`:
```bash
echo "dist/logs/" >> .gitignore
```

---

## What It Detects

### Critical Errors (Build Fails)

| Pattern | Risk |
|---------|------|
| `posts_per_page => -1` | Memory exhaustion |
| `numberposts => -1` | Memory exhaustion |
| `nopaging => true` | Disables all limits |
| `wc_get_orders(['limit' => -1])` | WooCommerce memory crash |
| `get_terms()` without `number` | Term table explosion |
| `pre_get_posts` forcing unbounded | Silent performance killer |
| Unbounded SQL on `wp_terms` | Full table scans |

### Warnings (Review Recommended)

| Pattern | Risk | Impact |
|---------|------|--------|
| `ORDER BY RAND()` | Full table scan | `[HIGH]` |
| `set_transient()` without expiration | Stale data, bloated DB | `[MEDIUM]` |
| N+1 patterns (meta in loops) | Query multiplication | `[MEDIUM]` |
| `current_time('timestamp')` | Timezone issues | `[LOW]` |

---

## Suppressing False Positives

Add `phpcs:ignore` comment on the line before:

```php
// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- Intentional for display
$time = current_time( 'timestamp' );
```

---

## Example Output

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  Neochrome WP Toolkit - Performance Checker v1.0.29
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

▸ Unbounded posts_per_page [CRITICAL]
  ✓ Passed

▸ Unbounded numberposts [CRITICAL]
  ✓ Passed

▸ Timezone-sensitive patterns [LOW]
  ⚠ WARNING (2 occurrence(s) without phpcs:ignore)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  SUMMARY
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  Errors:   0
  Warnings: 1

✓ All critical checks passed
```

---

## For AI Assistants

**Recommended workflow:**

1. Run `composer audit:verbose` (or `./dist/bin/check-performance.sh --paths "." --verbose`)
2. If errors found, fix them before proceeding
3. For warnings, evaluate if they're intentional or need fixing
4. Create an audit report in `AUDITS/` folder with findings

---

## Links

- **Repository:** https://github.com/NeochromeTeam/neochrome-toolkit-automated-wp-code-testing
- **Issues:** https://github.com/NeochromeTeam/neochrome-toolkit-automated-wp-code-testing/issues
- **Contact:** noel@neochro.me
