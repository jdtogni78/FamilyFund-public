#!/usr/bin/env bash
# Run ONLY the long-running "nightly" test group.
#
# The default `bin/test.sh` / `php artisan test` run skips the @group nightly
# tests (excluded in phpunit.xml) so everyday feedback stays fast (~6 min vs
# ~17 min). This wrapper runs exactly those slow tests via `--group=nightly`,
# which overrides the config exclude. Intended for a nightly CI/cron job.
#
# Currently tagged nightly (each >=50s): AclMatrixTest (~7m, full route×role
# sweep), PDFTest (wkhtmltopdf renders), SmokeTest, OperationsControllerTest.
#
# Delegates to bin/test.sh, so the same Vite-build refresh and container
# autodetection ($FF_CONTAINER -> ffacl-familyfund-1 -> familyfund) apply.
# Extra args pass through to `php artisan test`.
#
# Usage:
#   bin/test-nightly.sh
#   bin/test-nightly.sh --filter=PDFTest
#   FF_CONTAINER=familyfund-test0 bin/test-nightly.sh

set -euo pipefail

exec "$(dirname "$0")/test.sh" --group=nightly "$@"
