#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUTPUT_DIR="${ROOT_DIR}/dist"
OUTPUT_FILE="${OUTPUT_DIR}/erp-omd-sprint-10.zip"

mkdir -p "${OUTPUT_DIR}"
rm -f "${OUTPUT_FILE}"

cd "${ROOT_DIR}"
zip -r "${OUTPUT_FILE}" erp-omd >/dev/null

echo "Built: ${OUTPUT_FILE}"
