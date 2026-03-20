#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

cd "${ROOT_DIR}"

find erp-omd -name '*.php' -print0 | xargs -0 -n1 php -l
php tests/time-entry-service-test.php
php tests/project-financial-service-test.php
php tests/estimate-service-test.php
php tests/reporting-service-test.php
./scripts/build-sprint-6-zip.sh
