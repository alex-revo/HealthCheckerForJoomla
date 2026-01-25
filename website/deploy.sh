#!/bin/bash
# Deploy Health Checker Website to Cloudflare Workers
# and notify search engines via IndexNow
#
# Usage: ./deploy.sh [--skip-indexnow]

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Check for skip flag
SKIP_INDEXNOW=false
if [ "$1" == "--skip-indexnow" ]; then
    SKIP_INDEXNOW=true
fi

echo "=== Deploying Health Checker Website ==="
echo ""

# Step 1: Build the website
echo "Step 1: Building website..."
./build.sh
echo ""

# Step 2: Deploy to Cloudflare Workers
echo "Step 2: Deploying to Cloudflare Workers..."
npx wrangler deploy
echo ""

# Step 3: Notify search engines (optional)
if [ "$SKIP_INDEXNOW" = true ]; then
    echo "Step 3: Skipping IndexNow notification (--skip-indexnow flag)"
else
    echo "Step 3: Notifying search engines via IndexNow..."
    # Wait a moment for deployment to propagate
    echo "Waiting 5 seconds for deployment to propagate..."
    sleep 5
    ./indexnow-notify.sh
fi

echo ""
echo "=== Deployment Complete ==="
echo "Website: https://www.joomlahealthchecker.com"
