#!/bin/bash
set -e
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$DIR"

echo "[$(date -Iseconds)] Pulling latest demo files from GitHub..."
git fetch origin main
git reset --hard FETCH_HEAD
git clean -fd

# Locate Moodle directory
if [ -n "$MOODLE_DIR" ] && [ -d "$MOODLE_DIR" ]; then
    TARGET_DIR="$MOODLE_DIR"
elif [ -f "/home/aisim/public_html/moodle/config.php" ]; then
    TARGET_DIR="/home/aisim/public_html/moodle"
elif [ -f "/home/aisim/public_html/config.php" ]; then
    TARGET_DIR="/home/aisim/public_html"
elif [ -d "/home/aisim/public_html/moodle" ]; then
    TARGET_DIR="/home/aisim/public_html/moodle"
elif [ -d "/home/aisim/public_html" ]; then
    TARGET_DIR="/home/aisim/public_html"
elif [ -d "/var/www/moodle" ]; then
    TARGET_DIR="/var/www/moodle"
else
    echo "ERROR: Could not locate Moodle directory! Set MOODLE_DIR in environment."
    exit 1
fi

echo "[$(date -Iseconds)] Deploying to ${TARGET_DIR}..."
cp demo.php "${TARGET_DIR}/"
cp aisim-demo.mbz "${TARGET_DIR}/"
cp aisim-demo-sr.mbz "${TARGET_DIR}/"
cp aisim-demo-sk.mbz "${TARGET_DIR}/"
cp aisim-demo-pt.mbz "${TARGET_DIR}/"
if [ -d cards ]; then
    mkdir -p "${TARGET_DIR}/cards"
    cp -r cards/* "${TARGET_DIR}/cards/"
fi
if [ -f tiny_aisim-0.10.0.zip ]; then
    cp tiny_aisim-0.10.0.zip "${TARGET_DIR}/"
    echo "[$(date -Iseconds)] Extracting tiny_aisim-0.10.0.zip into lib/editor/tiny/plugins..."
    unzip -o tiny_aisim-0.10.0.zip -d "${TARGET_DIR}/lib/editor/tiny/plugins/" || true
fi

echo "[$(date -Iseconds)] Running plugin upgrades if any..."
/usr/bin/php "${TARGET_DIR}/admin/cli/upgrade.php" --non-interactive || true

echo "[$(date -Iseconds)] Purging Moodle caches..."
/usr/bin/php "${TARGET_DIR}/admin/cli/purge_caches.php" || true

echo "[$(date -Iseconds)] Running demo.php reset..." | tee "${TARGET_DIR}/deploy_log.txt"
/usr/bin/php "${TARGET_DIR}/demo.php" reset 2>&1 | tee -a "${TARGET_DIR}/deploy_log.txt"
echo "[$(date -Iseconds)] AISim demo deploy finished successfully!" | tee -a "${TARGET_DIR}/deploy_log.txt"
pm2 reload aisim-webhook >/dev/null 2>&1 || true
