#!/bin/bash
# Multi-environment Docker Compose helper
# Usage: ./launch_docker.sh <nickname> [docker-compose commands]
#
# Examples:
#   ./launch_docker.sh acl up -d              # Start ACL environment
#   ./launch_docker.sh feature-xyz up -d      # Start any feature branch
#   ./launch_docker.sh acl down               # Stop environment
#   ./launch_docker.sh acl exec familyfund bash
#
# Port assignments are stored in .dc-ports to avoid conflicts.
# Delete .dc-ports to reset all port assignments.

set -e

PORTS_FILE=".dc-ports"

if [ -z "$1" ]; then
    echo "Usage: ./launch_docker.sh <nickname> [docker-compose commands]"
    echo ""
    echo "Examples:"
    echo "  ./launch_docker.sh acl up -d"
    echo "  ./launch_docker.sh feature-login up -d"
    echo "  ./launch_docker.sh acl down"
    echo "  ./launch_docker.sh acl exec familyfund bash"
    echo ""
    echo "Current environments:"
    if [ -f "$PORTS_FILE" ]; then
        cat "$PORTS_FILE" | while IFS='=' read -r name offset; do
            echo "  $name -> localhost:$((3000 + offset))"
        done
    else
        echo "  (none)"
    fi
    exit 1
fi

NICKNAME="$1"
shift

# Get or assign port offset for this nickname
get_offset() {
    local name="$1"

    # Check if already assigned
    if [ -f "$PORTS_FILE" ]; then
        local existing=$(grep "^${name}=" "$PORTS_FILE" | cut -d'=' -f2)
        if [ -n "$existing" ]; then
            echo "$existing"
            return
        fi
    fi

    # Find next available offset (0-99)
    local used_offsets=""
    if [ -f "$PORTS_FILE" ]; then
        used_offsets=$(cut -d'=' -f2 "$PORTS_FILE" | tr '\n' ' ')
    fi

    for i in $(seq 0 99); do
        if ! echo "$used_offsets" | grep -qw "$i"; then
            # Save assignment
            echo "${name}=${i}" >> "$PORTS_FILE"
            echo "$i"
            return
        fi
    done

    echo "Error: No available port offsets" >&2
    exit 1
}

OFFSET=$(get_offset "$NICKNAME")

APP_PORT=$((3000 + OFFSET))
DB_PORT=$((3306 + OFFSET))
MAIL_SMTP_PORT=$((1025 + OFFSET))
MAIL_UI_PORT=$((8025 + OFFSET))
CHART_PORT=$((3400 + OFFSET))

export COMPOSE_PROJECT_NAME="familyfund-${NICKNAME}"
export FF_NICKNAME="${NICKNAME}"
export FF_APP_PORT="${APP_PORT}"
export FF_DB_PORT="${DB_PORT}"
export FF_MAIL_SMTP_PORT="${MAIL_SMTP_PORT}"
export FF_MAIL_UI_PORT="${MAIL_UI_PORT}"
export FF_CHART_PORT="${CHART_PORT}"
export FF_DB_NAME="familyfund_${NICKNAME}"
export FF_DATADIR="./datadir_${NICKNAME}"

echo "Environment: ${NICKNAME}"
echo "  App:       http://localhost:${APP_PORT}"
echo "  DB:        localhost:${DB_PORT}"
echo "  Mail UI:   http://localhost:${MAIL_UI_PORT}"
echo "  Database:  ${FF_DB_NAME}"
echo ""

docker compose -f docker-compose.yml -f docker-compose.env.yml "$@"
