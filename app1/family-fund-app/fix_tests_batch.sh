#!/bin/bash

# Test Fix Batch Script
# Usage: ./fix_tests_batch.sh TestClass1 TestClass2 TestClass3
# Or with file: ./fix_tests_batch.sh @test_failures_list.txt

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROMPT_FILE="$SCRIPT_DIR/TEST_FIX_PROMPT.md"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo "================================================================"
echo "Test Fix Batch Processor"
echo "================================================================"
echo

# Check if prompt file exists
if [ ! -f "$PROMPT_FILE" ]; then
    echo -e "${RED}ERROR: Prompt file not found: $PROMPT_FILE${NC}"
    exit 1
fi

# Check if claude CLI is available
if ! command -v claude &> /dev/null; then
    echo -e "${RED}ERROR: claude CLI not found. Please install claude-cli${NC}"
    echo "See: https://github.com/anthropics/claude-cli"
    exit 1
fi

# Function to process a single test
process_test() {
    local testname="$1"
    local logfile="$SCRIPT_DIR/test_fix_${testname}_$(date +%Y%m%d_%H%M%S).log"

    echo -e "${YELLOW}Processing: $testname${NC}"
    echo "Log file: $logfile"
    echo

    # Create the prompt for this specific test
    local test_prompt="Read the test fix instructions in @${PROMPT_FILE}

Execute these instructions for the failing test: ${testname}

Important:
- Run the test first to see the exact error
- Follow the systematic debugging process
- Fix the root cause, not just symptoms
- Run the test multiple times to ensure it passes consistently
- Verify no other tests broke
- Commit changes when done

Test to fix: ${testname}"

    # Run claude non-interactively
    echo "$test_prompt" | claude --no-interactive --model opus-4 2>&1 | tee "$logfile"

    local exit_code=${PIPESTATUS[1]}

    if [ $exit_code -eq 0 ]; then
        echo -e "${GREEN}✓ Completed: $testname${NC}"
        echo
    else
        echo -e "${RED}✗ Failed: $testname (exit code: $exit_code)${NC}"
        echo "Check log: $logfile"
        echo
        return 1
    fi

    # Pause between tests to avoid rate limiting
    echo "Waiting 5 seconds before next test..."
    sleep 5
}

# Parse arguments
if [ $# -eq 0 ]; then
    echo "Usage: $0 <test1> <test2> ... OR $0 @test_failures_list.txt"
    echo
    echo "Examples:"
    echo "  $0 AssetPriceControllerExtTest ScheduledJobControllerExtTest"
    echo "  $0 @test_failures_list.txt"
    echo
    echo "File list format (one per line):"
    echo "  AssetPriceControllerExtTest"
    echo "  ScheduledJobControllerExtTest"
    echo "  FundSetupTest"
    exit 1
fi

# Process file list
TESTS=()

for arg in "$@"; do
    if [[ $arg == @* ]]; then
        # Load from file
        list_file="${arg:1}"
        if [ ! -f "$list_file" ]; then
            echo -e "${RED}ERROR: File list not found: $list_file${NC}"
            exit 1
        fi
        while IFS= read -r line; do
            # Skip empty lines and comments
            [[ -z "$line" || "$line" =~ ^# ]] && continue
            TESTS+=("$line")
        done < "$list_file"
    else
        # Direct argument
        TESTS+=("$arg")
    fi
done

# Confirm before proceeding
echo "Tests to fix:"
for test in "${TESTS[@]}"; do
    echo "  - $test"
done
echo
echo "Total: ${#TESTS[@]} test(s)"
echo

read -p "Continue? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Cancelled."
    exit 0
fi

# Process each test
SUCCESS=0
FAILED=0

for test in "${TESTS[@]}"; do
    if process_test "$test"; then
        ((SUCCESS++))
    else
        ((FAILED++))
    fi
done

# Summary
echo "================================================================"
echo "Batch Processing Complete"
echo "================================================================"
echo -e "${GREEN}Successful: $SUCCESS${NC}"
if [ $FAILED -gt 0 ]; then
    echo -e "${RED}Failed: $FAILED${NC}"
fi
echo

# Log to tracking file
echo "# Last batch run: $(date)" >> "$SCRIPT_DIR/TEST_FIX_LOG.md"
echo "- Processed: ${#TESTS[@]} tests" >> "$SCRIPT_DIR/TEST_FIX_LOG.md"
echo "- Successful: $SUCCESS" >> "$SCRIPT_DIR/TEST_FIX_LOG.md"
echo "- Failed: $FAILED" >> "$SCRIPT_DIR/TEST_FIX_LOG.md"
echo >> "$SCRIPT_DIR/TEST_FIX_LOG.md"

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}All tests processed successfully!${NC}"
    exit 0
else
    echo -e "${YELLOW}Some tests failed to fix. Check logs for details.${NC}"
    exit 1
fi
