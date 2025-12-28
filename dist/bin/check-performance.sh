#!/usr/bin/env bash
#
# Neochrome WP Toolkit - Performance Check Script
# Version: 1.0.30
#
# Local runner for grep-based performance pattern detection.
# This mirrors the GitHub Actions workflow for local development.
#
# Usage:
#   ./bin/check-performance.sh [options]
#
# Options:
#   --paths "dir1 dir2"   Paths to scan (default: current directory)
#   --format text|json    Output format (default: text)
#   --strict              Fail on warnings (N+1 patterns)
#   --verbose             Show all matches, not just first occurrence
#   --no-log              Disable logging to file
#   --help                Show this help message

# Note: We intentionally do NOT use 'set -e' here because:
# 1. ((var++)) returns exit code 1 when var is 0, which would cause immediate exit
# 2. grep returning no matches (exit 1) is expected behavior we handle explicitly
# 3. We manage our own error tracking with ERRORS/WARNINGS counters

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Defaults
PATHS="."
STRICT=false
VERBOSE=false
ENABLE_LOGGING=true
OUTPUT_FORMAT="text"  # text or json
# Note: 'tests' exclusion is dynamically removed when --paths targets a tests directory
EXCLUDE_DIRS="vendor node_modules .git tests"

# JSON findings collection (initialized as empty)
declare -a JSON_FINDINGS=()
declare -a JSON_CHECKS=()

# Parse arguments
while [[ $# -gt 0 ]]; do
  case $1 in
    --paths)
      PATHS="$2"
      shift 2
      ;;
    --format)
      OUTPUT_FORMAT="$2"
      if [[ "$OUTPUT_FORMAT" != "text" && "$OUTPUT_FORMAT" != "json" ]]; then
        echo "Error: --format must be 'text' or 'json'"
        exit 1
      fi
      shift 2
      ;;
    --strict)
      STRICT=true
      shift
      ;;
    --verbose)
      VERBOSE=true
      shift
      ;;
    --no-log)
      ENABLE_LOGGING=false
      shift
      ;;
    --help)
      head -30 "$0" | tail -25
      exit 0
      ;;
    *)
      echo "Unknown option: $1"
      exit 1
      ;;
  esac
done

# If scanning a tests directory, remove 'tests' from exclusions
# Use portable method (no \b word boundary which is GNU-specific)
if echo "$PATHS" | grep -q "tests"; then
  EXCLUDE_DIRS="vendor node_modules .git"
fi

# Build exclude arguments
EXCLUDE_ARGS=""
for dir in $EXCLUDE_DIRS; do
  EXCLUDE_ARGS="$EXCLUDE_ARGS --exclude-dir=$dir"
done

# Setup logging
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$SCRIPT_DIR")"
LOG_DIR="$PLUGIN_DIR/logs"
LOG_FILE=""

if [ "$ENABLE_LOGGING" = true ]; then
  # Create logs directory if it doesn't exist
  mkdir -p "$LOG_DIR"

  # Generate timestamp in UTC (YYYY-MM-DD-HHMMSS-UTC format)
  TIMESTAMP=$(date -u +"%Y-%m-%d-%H%M%S-UTC")

  # Use appropriate file extension based on format
  if [ "$OUTPUT_FORMAT" = "json" ]; then
    LOG_FILE="$LOG_DIR/$TIMESTAMP.json"
    # For JSON mode, no header - just redirect output to log file
    exec > >(tee "$LOG_FILE")
    exec 2>&1
  else
    LOG_FILE="$LOG_DIR/$TIMESTAMP.log"

    # Write log header with metadata (text mode only)
    {
      echo "========================================================================"
      echo "Neochrome WP Toolkit - Performance Check Log"
      echo "========================================================================"
      echo ""
      echo "Timestamp (UTC):  $(date -u +"%Y-%m-%d %H:%M:%S")"
      echo "Script Version:   1.0.30"
      echo "Paths Scanned:    $PATHS"
      echo "Strict Mode:      $STRICT"
      echo "Verbose Mode:     $VERBOSE"
      echo "Exclude Dirs:     $EXCLUDE_DIRS"

      # Try to get git commit hash if available
      if command -v git &> /dev/null && git rev-parse --git-dir > /dev/null 2>&1; then
        GIT_COMMIT=$(git rev-parse --short HEAD 2>/dev/null || echo "N/A")
        GIT_BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "N/A")
        echo "Git Commit:       $GIT_COMMIT"
        echo "Git Branch:       $GIT_BRANCH"
      fi

      echo ""
      echo "========================================================================"
      echo ""
    } > "$LOG_FILE"

    # Redirect all output to both terminal and log file
    # We'll use process substitution to tee output
    exec > >(tee -a "$LOG_FILE")
    exec 2>&1
  fi
fi

# Function to log exit (defined early so trap can use it)
log_exit() {
  local exit_code=$1
  # Only write footer for text mode logs
  if [ "$ENABLE_LOGGING" = true ] && [ -n "$LOG_FILE" ] && [ "$OUTPUT_FORMAT" = "text" ]; then
    {
      echo ""
      echo "========================================================================"
      echo "End Timestamp (UTC): $(date -u +"%Y-%m-%d %H:%M:%S")"
      echo "Exit Code: $exit_code"
      echo "========================================================================"
    } >> "$LOG_FILE"
  fi
}

# Trap to ensure log footer is written even on unexpected exit or interrupt
if [ "$ENABLE_LOGGING" = true ]; then
  trap 'log_exit $?' EXIT
  trap 'exit 130' INT  # Ctrl+C
  trap 'exit 143' TERM # kill
fi

# ============================================================================
# JSON Output Helpers
# ============================================================================

# Escape string for JSON (handles quotes, backslashes, newlines)
json_escape() {
  local str="$1"
  str="${str//\\/\\\\}"      # Escape backslashes first
  str="${str//\"/\\\"}"      # Escape double quotes
  str="${str//$'\n'/\\n}"    # Escape newlines
  str="${str//$'\r'/\\r}"    # Escape carriage returns
  str="${str//$'\t'/\\t}"    # Escape tabs
  printf '%s' "$str"
}

# Add a finding to the JSON findings array
# Usage: add_json_finding "rule-id" "error|warning" "CRITICAL|HIGH|MEDIUM|LOW" "file" "line" "message" "code_snippet"
add_json_finding() {
  local rule_id="$1"
  local severity="$2"
  local impact="$3"
  local file="$4"
  local line="$5"
  local message="$6"
  local code="$7"

  local finding=$(cat <<EOF
{"id":"$(json_escape "$rule_id")","severity":"$severity","impact":"$impact","file":"$(json_escape "$file")","line":$line,"message":"$(json_escape "$message")","code":"$(json_escape "$code")"}
EOF
)
  JSON_FINDINGS+=("$finding")
}

# Add a check result to the JSON checks array
# Usage: add_json_check "Check Name" "CRITICAL|HIGH|MEDIUM|LOW" "passed|failed" count
add_json_check() {
  local name="$1"
  local impact="$2"
  local status="$3"
  local count="$4"

  local check=$(cat <<EOF
{"name":"$(json_escape "$name")","impact":"$impact","status":"$status","findings_count":$count}
EOF
)
  JSON_CHECKS+=("$check")
}

# Output final JSON
output_json() {
  local exit_code="$1"
  local timestamp=$(date -u +"%Y-%m-%dT%H:%M:%SZ")

  # Build findings array
  local findings_json=""
  local first=true
  for finding in "${JSON_FINDINGS[@]}"; do
    if [ "$first" = true ]; then
      findings_json="$finding"
      first=false
    else
      findings_json="$findings_json,$finding"
    fi
  done

  # Build checks array
  local checks_json=""
  first=true
  for check in "${JSON_CHECKS[@]}"; do
    if [ "$first" = true ]; then
      checks_json="$check"
      first=false
    else
      checks_json="$checks_json,$check"
    fi
  done

  cat <<EOF
{
  "version": "1.0.30",
  "timestamp": "$timestamp",
  "paths_scanned": "$(json_escape "$PATHS")",
  "strict_mode": $STRICT,
  "summary": {
    "total_errors": $ERRORS,
    "total_warnings": $WARNINGS,
    "exit_code": $exit_code
  },
  "findings": [$findings_json],
  "checks": [$checks_json]
}
EOF
}

# Conditional echo - only outputs in text mode
text_echo() {
  if [ "$OUTPUT_FORMAT" = "text" ]; then
    echo -e "$@"
  fi
}

# ============================================================================
# Main Script Output
# ============================================================================

text_echo "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
text_echo "${BLUE}  Neochrome WP Toolkit - Performance Checker v1.0.30${NC}"
text_echo "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
text_echo ""
text_echo "Scanning paths: $PATHS"
text_echo "Strict mode: $STRICT"
if [ "$ENABLE_LOGGING" = true ] && [ "$OUTPUT_FORMAT" = "text" ]; then
  text_echo "Logging to: $LOG_FILE"
fi
text_echo ""

ERRORS=0
WARNINGS=0

# Function to run a check with impact scoring
# Usage: run_check "ERROR|WARNING" "CRITICAL|HIGH|MEDIUM|LOW" "Check name" "rule-id" patterns...
run_check() {
  local level="$1"    # ERROR or WARNING
  local impact="$2"   # CRITICAL, HIGH, MEDIUM, or LOW
  local name="$3"     # Check name
  local rule_id="$4"  # Rule ID for JSON output
  shift 4             # Remove first four args, rest are patterns
  local patterns="$@" # All remaining args are grep patterns

  # Format impact badge
  local impact_badge=""
  case $impact in
    CRITICAL) impact_badge="${RED}[CRITICAL]${NC}" ;;
    HIGH)     impact_badge="${RED}[HIGH]${NC}" ;;
    MEDIUM)   impact_badge="${YELLOW}[MEDIUM]${NC}" ;;
    LOW)      impact_badge="${BLUE}[LOW]${NC}" ;;
  esac

  text_echo "${BLUE}▸ $name ${impact_badge}${NC}"

  # Run grep with all patterns
  local result
  local finding_count=0
  local severity="error"
  [ "$level" = "WARNING" ] && severity="warning"

  if result=$(grep -rn $EXCLUDE_ARGS --include="*.php" $patterns $PATHS 2>/dev/null); then
    finding_count=$(echo "$result" | wc -l | tr -d ' ')

    if [ "$level" = "ERROR" ]; then
      text_echo "${RED}  ✗ FAILED${NC}"
      if [ "$OUTPUT_FORMAT" = "text" ]; then
        echo "$result" | head -10
        if [ "$VERBOSE" = "false" ] && [ "$finding_count" -gt 10 ]; then
          echo "  ... and more (use --verbose to see all)"
        fi
      fi
      ((ERRORS++))
    else
      text_echo "${YELLOW}  ⚠ WARNING${NC}"
      if [ "$OUTPUT_FORMAT" = "text" ]; then
        echo "$result" | head -5
      fi
      ((WARNINGS++))
    fi

    # Collect findings for JSON output
    while IFS= read -r line; do
      # Parse grep output: file:line:code
      local file=$(echo "$line" | cut -d: -f1)
      local lineno=$(echo "$line" | cut -d: -f2)
      local code=$(echo "$line" | cut -d: -f3-)
      add_json_finding "$rule_id" "$severity" "$impact" "$file" "$lineno" "$name" "$code"
    done <<< "$result"

    add_json_check "$name" "$impact" "failed" "$finding_count"
  else
    text_echo "${GREEN}  ✓ Passed${NC}"
    add_json_check "$name" "$impact" "passed" 0
  fi
  text_echo ""
}

text_echo "${RED}━━━ CRITICAL CHECKS (will fail build) ━━━${NC}"
text_echo ""

run_check "ERROR" "CRITICAL" "Unbounded posts_per_page" "unbounded-posts-per-page" \
  "-e posts_per_page[[:space:]]*=>[[:space:]]*-1"

run_check "ERROR" "CRITICAL" "Unbounded numberposts" "unbounded-numberposts" \
  "-e numberposts[[:space:]]*=>[[:space:]]*-1"

run_check "ERROR" "CRITICAL" "nopaging => true" "nopaging-true" \
  "-e nopaging[[:space:]]*=>[[:space:]]*true"

run_check "ERROR" "CRITICAL" "Unbounded wc_get_orders limit" "unbounded-wc-get-orders" \
  "-e 'limit'[[:space:]]*=>[[:space:]]*-1"

# get_terms check - more complex, needs context analysis
text_echo "${BLUE}▸ get_terms without number limit ${RED}[CRITICAL]${NC}"
TERMS_FILES=$(grep -rln $EXCLUDE_ARGS --include="*.php" -e "get_terms[[:space:]]*(" $PATHS 2>/dev/null || true)
TERMS_UNBOUNDED=false
TERMS_FINDING_COUNT=0
if [ -n "$TERMS_FILES" ]; then
  for file in $TERMS_FILES; do
    # Check if file has get_terms without 'number' or "number" nearby (within 5 lines)
    # Support both single and double quotes
    if ! grep -A5 "get_terms[[:space:]]*(" "$file" 2>/dev/null | grep -q -e "'number'" -e '"number"'; then
      text_echo "  $file: get_terms() may be missing 'number' parameter"
      # Get line number for JSON
      lineno=$(grep -n "get_terms[[:space:]]*(" "$file" 2>/dev/null | head -1 | cut -d: -f1)
      add_json_finding "get-terms-no-limit" "error" "CRITICAL" "$file" "${lineno:-0}" "get_terms() may be missing 'number' parameter" "get_terms("
      TERMS_UNBOUNDED=true
      ((TERMS_FINDING_COUNT++))
    fi
  done
fi
if [ "$TERMS_UNBOUNDED" = true ]; then
  text_echo "${RED}  ✗ FAILED${NC}"
  ((ERRORS++))
  add_json_check "get_terms without number limit" "CRITICAL" "failed" "$TERMS_FINDING_COUNT"
else
  text_echo "${GREEN}  ✓ Passed${NC}"
  add_json_check "get_terms without number limit" "CRITICAL" "passed" 0
fi
text_echo ""

# pre_get_posts unbounded check - files that hook pre_get_posts and set unbounded queries
text_echo "${BLUE}▸ pre_get_posts forcing unbounded queries ${RED}[CRITICAL]${NC}"
PRE_GET_POSTS_UNBOUNDED=false
PRE_GET_POSTS_FINDING_COUNT=0
PRE_GET_POSTS_FILES=$(grep -rln $EXCLUDE_ARGS --include="*.php" -e "add_action.*pre_get_posts\|add_filter.*pre_get_posts" $PATHS 2>/dev/null || true)
if [ -n "$PRE_GET_POSTS_FILES" ]; then
  for file in $PRE_GET_POSTS_FILES; do
    # Check if file sets posts_per_page to -1 or nopaging to true
    if grep -q "set[[:space:]]*([[:space:]]*['\"]posts_per_page['\"][[:space:]]*,[[:space:]]*-1" "$file" 2>/dev/null || \
       grep -q "set[[:space:]]*([[:space:]]*['\"]nopaging['\"][[:space:]]*,[[:space:]]*true" "$file" 2>/dev/null; then
      text_echo "  $file: pre_get_posts hook sets unbounded query"
      lineno=$(grep -n "pre_get_posts" "$file" 2>/dev/null | head -1 | cut -d: -f1)
      add_json_finding "pre-get-posts-unbounded" "error" "CRITICAL" "$file" "${lineno:-0}" "pre_get_posts hook sets unbounded query" "pre_get_posts"
      PRE_GET_POSTS_UNBOUNDED=true
      ((PRE_GET_POSTS_FINDING_COUNT++))
    fi
  done
fi
if [ "$PRE_GET_POSTS_UNBOUNDED" = true ]; then
  text_echo "${RED}  ✗ FAILED${NC}"
  ((ERRORS++))
  add_json_check "pre_get_posts forcing unbounded queries" "CRITICAL" "failed" "$PRE_GET_POSTS_FINDING_COUNT"
else
  text_echo "${GREEN}  ✓ Passed${NC}"
  add_json_check "pre_get_posts forcing unbounded queries" "CRITICAL" "passed" 0
fi
text_echo ""

# Unbounded direct SQL on terms tables
# Look for lines with wpdb->terms or wpdb->term_taxonomy that don't have LIMIT on the same line
text_echo "${BLUE}▸ Unbounded SQL on wp_terms/wp_term_taxonomy ${RED}[HIGH]${NC}"
TERMS_SQL_UNBOUNDED=false
TERMS_SQL_FINDING_COUNT=0
# Find lines referencing terms tables in SQL context
TERMS_SQL_MATCHES=$(grep -rn $EXCLUDE_ARGS --include="*.php" -E '\$wpdb->(terms|term_taxonomy)' $PATHS 2>/dev/null || true)
if [ -n "$TERMS_SQL_MATCHES" ]; then
  # Filter out lines that have LIMIT (case-insensitive to catch both 'LIMIT' and 'limit')
  UNBOUNDED_MATCHES=$(echo "$TERMS_SQL_MATCHES" | grep -vi "LIMIT" || true)
  if [ -n "$UNBOUNDED_MATCHES" ]; then
    if [ "$OUTPUT_FORMAT" = "text" ]; then
      echo "$UNBOUNDED_MATCHES" | head -5 | while read line; do
        echo "  $line"
      done
    fi
    # Collect findings for JSON
    while IFS= read -r line; do
      _file=$(echo "$line" | cut -d: -f1)
      _lineno=$(echo "$line" | cut -d: -f2)
      _code=$(echo "$line" | cut -d: -f3-)
      add_json_finding "unbounded-terms-sql" "error" "HIGH" "$_file" "${_lineno:-0}" "Unbounded SQL on wp_terms/wp_term_taxonomy" "$_code"
      ((TERMS_SQL_FINDING_COUNT++))
    done <<< "$UNBOUNDED_MATCHES"
    TERMS_SQL_UNBOUNDED=true
  fi
fi
if [ "$TERMS_SQL_UNBOUNDED" = true ]; then
  text_echo "${RED}  ✗ FAILED${NC}"
  ((ERRORS++))
  add_json_check "Unbounded SQL on wp_terms/wp_term_taxonomy" "HIGH" "failed" "$TERMS_SQL_FINDING_COUNT"
else
  text_echo "${GREEN}  ✓ Passed${NC}"
  add_json_check "Unbounded SQL on wp_terms/wp_term_taxonomy" "HIGH" "passed" 0
fi

text_echo ""
text_echo "${YELLOW}━━━ WARNING CHECKS (review recommended) ━━━${NC}"
text_echo ""

# Enhanced timezone check - skip lines with phpcs:ignore comments
text_echo "${BLUE}▸ Timezone-sensitive patterns (current_time/date) ${YELLOW}[LOW]${NC}"
TZ_WARNINGS=0
TZ_FINDING_COUNT=0
TZ_MATCHES=$(grep -rn $EXCLUDE_ARGS --include="*.php" \
  -e "current_time[[:space:]]*([[:space:]]*['\"]timestamp" \
  -e "date[[:space:]]*([[:space:]]*['\"][YmdHis-]*['\"]" \
  $PATHS 2>/dev/null || true)

if [ -n "$TZ_MATCHES" ]; then
  # Filter out lines that have phpcs:ignore nearby (check line before)
  FILTERED_MATCHES=""
  while IFS= read -r match; do
    file_line=$(echo "$match" | cut -d: -f1-2)
    file=$(echo "$match" | cut -d: -f1)
    line_num=$(echo "$match" | cut -d: -f2)
    code=$(echo "$match" | cut -d: -f3-)

    # Check if there's a phpcs:ignore comment on the line before or same line
    prev_line=$((line_num - 1))
    has_ignore=false

    # Check if current line or previous line has phpcs:ignore
    if sed -n "${prev_line}p;${line_num}p" "$file" 2>/dev/null | grep -q "phpcs:ignore"; then
      has_ignore=true
    fi

    if [ "$has_ignore" = false ]; then
      FILTERED_MATCHES="${FILTERED_MATCHES}${match}"$'\n'
      add_json_finding "timezone-sensitive-pattern" "warning" "LOW" "$file" "$line_num" "Timezone-sensitive pattern without phpcs:ignore" "$code"
      ((TZ_WARNINGS++)) || true
      ((TZ_FINDING_COUNT++)) || true
    fi
  done <<< "$TZ_MATCHES"

  if [ "$TZ_WARNINGS" -gt 0 ]; then
    text_echo "${YELLOW}  ⚠ WARNING ($TZ_WARNINGS occurrence(s) without phpcs:ignore)${NC}"
    if [ "$OUTPUT_FORMAT" = "text" ]; then
      if [ "$VERBOSE" = "true" ]; then
        echo "$FILTERED_MATCHES"
      else
        echo "$FILTERED_MATCHES" | head -5
        if [ "$TZ_WARNINGS" -gt 5 ]; then
          echo "  ... and $((TZ_WARNINGS - 5)) more (use --verbose to see all)"
        fi
      fi
    fi
    ((WARNINGS++))
    add_json_check "Timezone-sensitive patterns (current_time/date)" "LOW" "failed" "$TZ_FINDING_COUNT"
  else
    text_echo "${GREEN}  ✓ Passed (all occurrences have phpcs:ignore)${NC}"
    add_json_check "Timezone-sensitive patterns (current_time/date)" "LOW" "passed" 0
  fi
else
  text_echo "${GREEN}  ✓ Passed${NC}"
  add_json_check "Timezone-sensitive patterns (current_time/date)" "LOW" "passed" 0
fi
text_echo ""

run_check "WARNING" "HIGH" "Randomized ordering (ORDER BY RAND)" "order-by-rand" \
  "-e orderby[[:space:]]*=>[[:space:]]*['\"]rand['\"]" \
  "-E ORDER[[:space:]]+BY[[:space:]]+RAND\("

# LIKE queries with leading wildcards
text_echo "${BLUE}▸ LIKE queries with leading wildcards ${YELLOW}[MEDIUM]${NC}"
LIKE_WARNINGS=0
LIKE_ISSUES=""
LIKE_FINDING_COUNT=0

# Pattern 1: WP_Query meta_query with compare => 'LIKE' and value starting with %
# Look for 'compare' => 'LIKE' patterns in meta_query context
META_LIKE=$(grep -rn $EXCLUDE_ARGS --include="*.php" \
  -E "'compare'[[:space:]]*=>[[:space:]]*['\"]LIKE['\"]" \
  $PATHS 2>/dev/null || true)

if [ -n "$META_LIKE" ]; then
  # Check each match for nearby % wildcard at start of value
  while IFS= read -r match; do
    [ -z "$match" ] && continue
    file=$(echo "$match" | cut -d: -f1)
    line_num=$(echo "$match" | cut -d: -f2)
    code=$(echo "$match" | cut -d: -f3-)

    # Look at surrounding lines (5 before and after) for value starting with %
    start_line=$((line_num - 5))
    [ "$start_line" -lt 1 ] && start_line=1
    end_line=$((line_num + 5))

    # Check for 'value' => '%... pattern nearby
    if sed -n "${start_line},${end_line}p" "$file" 2>/dev/null | grep -qE "'value'[[:space:]]*=>[[:space:]]*['\"]%"; then
      LIKE_ISSUES="${LIKE_ISSUES}${match}"$'\n'
      add_json_finding "like-leading-wildcard" "warning" "MEDIUM" "$file" "$line_num" "LIKE query with leading wildcard prevents index use" "$code"
      ((LIKE_WARNINGS++)) || true
      ((LIKE_FINDING_COUNT++)) || true
    fi
  done <<< "$META_LIKE"
fi

# Pattern 2: Raw SQL with LIKE '%... (leading wildcard)
# Only match actual code, not comments (lines starting with * or //)
SQL_LIKE=$(grep -rn $EXCLUDE_ARGS --include="*.php" \
  -E "LIKE[[:space:]]+['\"]%" \
  $PATHS 2>/dev/null | grep -v "^[^:]*:[0-9]*:[[:space:]]*//" | grep -v "^[^:]*:[0-9]*:[[:space:]]*\*" || true)

if [ -n "$SQL_LIKE" ]; then
  while IFS= read -r match; do
    [ -z "$match" ] && continue
    file=$(echo "$match" | cut -d: -f1)
    line_num=$(echo "$match" | cut -d: -f2)
    code=$(echo "$match" | cut -d: -f3-)
    LIKE_ISSUES="${LIKE_ISSUES}${match}"$'\n'
    add_json_finding "like-leading-wildcard" "warning" "MEDIUM" "$file" "$line_num" "LIKE query with leading wildcard prevents index use" "$code"
    ((LIKE_WARNINGS++)) || true
    ((LIKE_FINDING_COUNT++)) || true
  done <<< "$SQL_LIKE"
fi

if [ "$LIKE_WARNINGS" -gt 0 ]; then
  text_echo "${YELLOW}  ⚠ WARNING - LIKE queries with leading wildcards prevent index use:${NC}"
  if [ "$OUTPUT_FORMAT" = "text" ]; then
    if [ "$VERBOSE" = "true" ]; then
      echo "$LIKE_ISSUES"
    else
      echo "$LIKE_ISSUES" | head -5
      if [ "$LIKE_WARNINGS" -gt 5 ]; then
        echo "  ... and $((LIKE_WARNINGS - 5)) more (use --verbose to see all)"
      fi
    fi
  fi
  ((WARNINGS++))
  add_json_check "LIKE queries with leading wildcards" "MEDIUM" "failed" "$LIKE_FINDING_COUNT"
else
  text_echo "${GREEN}  ✓ Passed${NC}"
  add_json_check "LIKE queries with leading wildcards" "MEDIUM" "passed" 0
fi
text_echo ""

# N+1 pattern check (simplified)
text_echo "${BLUE}▸ Potential N+1 patterns (meta in loops) ${YELLOW}[MEDIUM]${NC}"
N1_FILES=$(grep -rl $EXCLUDE_ARGS --include="*.php" -e "get_post_meta\|get_term_meta" $PATHS 2>/dev/null | \
           xargs -I{} grep -l "foreach\|while[[:space:]]*(" {} 2>/dev/null | head -5 || true)
N1_FINDING_COUNT=0
if [ -n "$N1_FILES" ]; then
  text_echo "${YELLOW}  ⚠ Files with potential N+1 patterns:${NC}"
  if [ "$OUTPUT_FORMAT" = "text" ]; then
    echo "$N1_FILES" | while read f; do echo "    - $f"; done
  fi
  # Collect findings for JSON
  while IFS= read -r f; do
    [ -z "$f" ] && continue
    add_json_finding "n-plus-1-pattern" "warning" "MEDIUM" "$f" "0" "File may contain N+1 query pattern (meta in loops)" ""
    ((N1_FINDING_COUNT++)) || true
  done <<< "$N1_FILES"
  ((WARNINGS++))
  add_json_check "Potential N+1 patterns (meta in loops)" "MEDIUM" "failed" "$N1_FINDING_COUNT"
else
  text_echo "${GREEN}  ✓ No obvious N+1 patterns${NC}"
  add_json_check "Potential N+1 patterns (meta in loops)" "MEDIUM" "passed" 0
fi
text_echo ""

# Transient abuse check - transients without expiration
text_echo "${BLUE}▸ Transients without expiration ${YELLOW}[MEDIUM]${NC}"
TRANSIENT_MATCHES=$(grep -rn $EXCLUDE_ARGS --include="*.php" -E "set_transient[[:space:]]*\(" $PATHS 2>/dev/null || true)
TRANSIENT_ABUSE=false
TRANSIENT_ISSUES=""
TRANSIENT_FINDING_COUNT=0

if [ -n "$TRANSIENT_MATCHES" ]; then
  while IFS= read -r match; do
    # Check if line contains a third parameter (expiration)
    # set_transient( $key, $value, $expiration ) - needs 3 params
    # Count commas in the line - should have at least 2 for proper usage
    comma_count=$(echo "$match" | tr -cd ',' | wc -c)
    if [ "$comma_count" -lt 2 ]; then
      file=$(echo "$match" | cut -d: -f1)
      line_num=$(echo "$match" | cut -d: -f2)
      code=$(echo "$match" | cut -d: -f3-)
      TRANSIENT_ISSUES="${TRANSIENT_ISSUES}${match}"$'\n'
      add_json_finding "transient-no-expiration" "warning" "MEDIUM" "$file" "$line_num" "Transient may be missing expiration parameter" "$code"
      TRANSIENT_ABUSE=true
      ((TRANSIENT_FINDING_COUNT++)) || true
    fi
  done <<< "$TRANSIENT_MATCHES"
fi

if [ "$TRANSIENT_ABUSE" = true ]; then
  text_echo "${YELLOW}  ⚠ WARNING - Transients may be missing expiration parameter:${NC}"
  if [ "$OUTPUT_FORMAT" = "text" ]; then
    echo "$TRANSIENT_ISSUES" | head -5
  fi
  ((WARNINGS++))
  add_json_check "Transients without expiration" "MEDIUM" "failed" "$TRANSIENT_FINDING_COUNT"
else
  text_echo "${GREEN}  ✓ Passed${NC}"
  add_json_check "Transients without expiration" "MEDIUM" "passed" 0
fi
text_echo ""

# Determine exit code
EXIT_CODE=0
if [ "$ERRORS" -gt 0 ]; then
  EXIT_CODE=1
elif [ "$STRICT" = "true" ] && [ "$WARNINGS" -gt 0 ]; then
  EXIT_CODE=1
fi

# Output based on format
if [ "$OUTPUT_FORMAT" = "json" ]; then
  output_json "$EXIT_CODE"
else
  # Summary (text mode)
  text_echo "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
  text_echo "${BLUE}  SUMMARY${NC}"
  text_echo "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
  text_echo ""
  text_echo "  Errors:   ${RED}$ERRORS${NC}"
  text_echo "  Warnings: ${YELLOW}$WARNINGS${NC}"
  text_echo ""

  if [ "$ERRORS" -gt 0 ]; then
    text_echo "${RED}✗ Check failed with $ERRORS error(s)${NC}"
  elif [ "$STRICT" = "true" ] && [ "$WARNINGS" -gt 0 ]; then
    text_echo "${YELLOW}✗ Check failed in strict mode with $WARNINGS warning(s)${NC}"
  else
    text_echo "${GREEN}✓ All critical checks passed!${NC}"
  fi
fi

exit $EXIT_CODE

