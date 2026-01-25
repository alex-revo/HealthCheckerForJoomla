#!/bin/bash
# IndexNow Notification Script
# Notifies search engines of all website pages after deployment
#
# Usage: ./indexnow-notify.sh [--dry-run]
#
# This script submits all pages from the sitemap to IndexNow-compatible
# search engines (Microsoft Bing, Yandex, Seznam, Naver).

set -e

# Configuration
INDEXNOW_KEY="0e6bc27f3c2d9335a6bfc58e1d5e1774"
HOST="www.joomlahealthchecker.com"
SITEMAP_PATH="public/sitemap.xml"

# IndexNow endpoints (all share the same protocol)
# We only need to submit to one - they share the submitted URLs
INDEXNOW_ENDPOINT="https://api.indexnow.org/indexnow"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check for dry-run flag
DRY_RUN=false
if [ "$1" == "--dry-run" ]; then
    DRY_RUN=true
    echo -e "${YELLOW}=== DRY RUN MODE ===${NC}"
    echo "No actual submissions will be made."
    echo ""
fi

echo "=== IndexNow Notification ==="
echo "Host: $HOST"
echo "Key: ${INDEXNOW_KEY:0:8}..."
echo ""

# Build URL list from sitemap
echo "Extracting URLs from sitemap..."

if [ ! -f "$SITEMAP_PATH" ]; then
    echo -e "${RED}Error: Sitemap not found at $SITEMAP_PATH${NC}"
    echo "Run the build first to generate the sitemap."
    exit 1
fi

# Extract URLs from sitemap XML
# The sitemap format is: <loc>https://...</loc>
URLS=$(grep -oP '(?<=<loc>)[^<]+' "$SITEMAP_PATH" 2>/dev/null || grep -o '<loc>[^<]*</loc>' "$SITEMAP_PATH" | sed 's/<loc>//g;s/<\/loc>//g')

# Add main index page if not in sitemap
MAIN_URL="https://${HOST}/"
if ! echo "$URLS" | grep -q "^${MAIN_URL}$"; then
    URLS="${MAIN_URL}"$'\n'"${URLS}"
fi

# Count URLs
URL_COUNT=$(echo "$URLS" | wc -l | tr -d ' ')
echo "Found $URL_COUNT URLs to submit"
echo ""

# Build JSON payload for batch submission
echo "Building IndexNow payload..."

# Create URL array for JSON
URL_JSON_ARRAY=$(echo "$URLS" | while read -r url; do
    [ -n "$url" ] && echo "    \"$url\""
done | paste -sd ',' -)

# Build the full JSON payload
JSON_PAYLOAD=$(cat <<EOF
{
  "host": "${HOST}",
  "key": "${INDEXNOW_KEY}",
  "keyLocation": "https://${HOST}/${INDEXNOW_KEY}.txt",
  "urlList": [
${URL_JSON_ARRAY}
  ]
}
EOF
)

# Show payload in dry-run mode
if [ "$DRY_RUN" = true ]; then
    echo "JSON Payload:"
    echo "$JSON_PAYLOAD" | head -20
    if [ $URL_COUNT -gt 15 ]; then
        echo "    ... ($(($URL_COUNT - 15)) more URLs)"
    fi
    echo ""
    echo -e "${YELLOW}Dry run complete. No submission made.${NC}"
    exit 0
fi

# Submit to IndexNow
echo "Submitting to IndexNow..."

RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$INDEXNOW_ENDPOINT" \
    -H "Content-Type: application/json; charset=utf-8" \
    -d "$JSON_PAYLOAD")

# Extract HTTP status code (last line)
HTTP_CODE=$(echo "$RESPONSE" | tail -1)
BODY=$(echo "$RESPONSE" | sed '$d')

echo ""
echo "Response:"
echo "  HTTP Status: $HTTP_CODE"

# Interpret response
case $HTTP_CODE in
    200)
        echo -e "  ${GREEN}Success: URLs submitted successfully${NC}"
        ;;
    202)
        echo -e "  ${GREEN}Accepted: URLs accepted for processing${NC}"
        ;;
    400)
        echo -e "  ${RED}Bad Request: Invalid format${NC}"
        echo "  Body: $BODY"
        exit 1
        ;;
    403)
        echo -e "  ${RED}Forbidden: Key not valid or not matching host${NC}"
        echo "  Verify the key file exists at: https://${HOST}/${INDEXNOW_KEY}.txt"
        exit 1
        ;;
    422)
        echo -e "  ${RED}Unprocessable: URLs don't belong to the host${NC}"
        exit 1
        ;;
    429)
        echo -e "  ${YELLOW}Too Many Requests: Rate limited. Try again later.${NC}"
        exit 1
        ;;
    *)
        echo -e "  ${YELLOW}Unexpected response: $HTTP_CODE${NC}"
        [ -n "$BODY" ] && echo "  Body: $BODY"
        ;;
esac

echo ""
echo "=== IndexNow Notification Complete ==="
echo "Submitted $URL_COUNT URLs to search engines"
echo ""
echo "Note: It may take time for search engines to crawl the submitted URLs."
echo "Check your search console for indexing status."
