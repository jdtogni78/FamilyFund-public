#!/bin/bash

# Coverage Improvement Batch Script
# Usage: ./improve_coverage_batch.sh Controller1 Controller2 Controller3
# Or with file: ./improve_coverage_batch.sh @file_list.txt

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROMPT_FILE="$SCRIPT_DIR/COVERAGE_IMPROVEMENT_PROMPT.md"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo "================================================================"
echo "Coverage Improvement Batch Processor"
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

# Function to process a single file
process_file() {
    local filename="$1"
    local logfile="$SCRIPT_DIR/coverage_improvement_${filename}_$(date +%Y%m%d_%H%M%S).log"

    echo -e "${YELLOW}Processing: $filename${NC}"
    echo "Log file: $logfile"
    echo

    # Create the prompt for this specific file
    local file_prompt="Read the coverage improvement instructions in @${PROMPT_FILE}

Execute these instructions for the file: ${filename}

Important:
- Follow all steps systematically
- Run all tests and verify 100% pass rate
- Commit changes with co-authorship when done
- Report final coverage percentage

File to improve: ${filename}"

    # Run claude non-interactively
    echo "$file_prompt" | claude --no-interactive --model opus-4 2>&1 | tee "$logfile"

    local exit_code=${PIPESTATUS[1]}

    if [ $exit_code -eq 0 ]; then
        echo -e "${GREEN}✓ Completed: $filename${NC}"
        echo
    else
        echo -e "${RED}✗ Failed: $filename (exit code: $exit_code)${NC}"
        echo "Check log: $logfile"
        echo
        return 1
    fi

    # Pause between files to avoid rate limiting
    echo "Waiting 5 seconds before next file..."
    sleep 5
}

# Parse arguments
if [ $# -eq 0 ]; then
    echo "Usage: $0 <file1> <file2> ... OR $0 @file_list.txt"
    echo
    echo "Examples:"
    echo "  $0 UserController AccountController"
    echo "  $0 @controllers_to_improve.txt"
    echo
    echo "File list format (one per line):"
    echo "  UserController"
    echo "  AccountController"
    echo "  FundPDF"
    exit 1
fi

# Process file list
FILES=()

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
            FILES+=("$line")
        done < "$list_file"
    else
        # Direct argument
        FILES+=("$arg")
    fi
done

# Confirm before proceeding
echo "Files to process:"
for file in "${FILES[@]}"; do
    echo "  - $file"
done
echo
echo "Total: ${#FILES[@]} file(s)"
echo

read -p "Continue? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Cancelled."
    exit 0
fi

# Process each file
SUCCESS=0
FAILED=0

for file in "${FILES[@]}"; do
    if process_file "$file"; then
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

# Update coverage TODO
echo "Updating COVERAGE_TODO.md..."
echo "# Last batch run: $(date)" >> "$SCRIPT_DIR/COVERAGE_TODO.md"
echo "- Processed: ${#FILES[@]} files" >> "$SCRIPT_DIR/COVERAGE_TODO.md"
echo "- Successful: $SUCCESS" >> "$SCRIPT_DIR/COVERAGE_TODO.md"
echo "- Failed: $FAILED" >> "$SCRIPT_DIR/COVERAGE_TODO.md"
echo

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}All files processed successfully!${NC}"
    exit 0
else
    echo -e "${YELLOW}Some files failed. Check logs for details.${NC}"
    exit 1
fi
