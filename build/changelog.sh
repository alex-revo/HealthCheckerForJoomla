#!/bin/bash

# Health Checker for Joomla - Changelog Generator
# Fetches GitHub releases and generates a markdown changelog page for VitePress docs

set -e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
OUTPUT_FILE="$PROJECT_ROOT/docs/USER/changelog.md"
REPO="mySites-guru/HealthCheckerForJoomla"
REPO_URL="https://github.com/$REPO"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${GREEN}Health Checker for Joomla - Changelog Generator${NC}"
echo "===================================================================="
echo ""

# Ensure gh is available
if ! command -v gh &> /dev/null; then
    echo -e "${RED}ERROR: GitHub CLI (gh) is required but not installed.${NC}"
    exit 1
fi

# Fetch all releases
echo -e "${YELLOW}Fetching releases from GitHub...${NC}"
RELEASES_JSON=$(gh release list --repo "$REPO" --limit 100 --json tagName,name,publishedAt --order asc)
RELEASE_TAGS=$(echo "$RELEASES_JSON" | python3 -c "
import sys, json
releases = json.load(sys.stdin)
# Sort by semver descending
def sort_key(r):
    tag = r['tagName'].lstrip('v')
    parts = tag.split('.')
    return tuple(int(p) for p in parts)
releases.sort(key=sort_key, reverse=True)
for r in releases:
    print(r['tagName'])
")

if [ -z "$RELEASE_TAGS" ]; then
    echo -e "${RED}ERROR: No releases found.${NC}"
    exit 1
fi

TOTAL=$(echo "$RELEASE_TAGS" | wc -l | tr -d ' ')
echo -e "${GREEN}Found $TOTAL releases${NC}"
echo ""

# Parse a release body and extract only changelog items
extract_changelog_items() {
    local body="$1"

    echo "$body" | python3 -c "
import sys

lines = sys.stdin.read().split('\n')
items = []
in_boilerplate = False
has_breaking = False
breaking_items = []

for line in lines:
    stripped = line.strip()

    # Detect boilerplate section headers and skip them
    if stripped.startswith('## Installation') or \
       stripped.startswith('## Requirements') or \
       stripped.startswith('## Usage') or \
       stripped.startswith('**What gets installed') or \
       stripped.startswith('**Full Changelog**') or \
       stripped.startswith('---'):
        in_boilerplate = True
        continue

    # Detect breaking changes header
    if stripped.startswith('## Breaking') or stripped.startswith('### Breaking'):
        in_boilerplate = False
        has_breaking = True
        continue

    # If we hit another header, reset boilerplate flag
    if stripped.startswith('## ') or stripped.startswith('### '):
        in_boilerplate = False
        continue

    if in_boilerplate:
        continue

    # Skip boilerplate bullet items (installation list items)
    if stripped.startswith('- ') and stripped.startswith('- ✓'):
        continue

    # Actual changelog items
    if stripped.startswith('- '):
        if has_breaking and not items:
            # Items right after breaking header
            breaking_items.append(stripped)
        else:
            items.append(stripped)
            has_breaking = False

# Output breaking items first, then regular items
for item in breaking_items:
    print('BREAKING:' + item)
for item in items:
    print(item)
"
}

# Build the markdown content
echo -e "${YELLOW}Processing releases...${NC}"

CHANGELOG_MD="---
title: Changelog
description: Complete history of features, fixes, and improvements across every release of Health Checker for Joomla.
---

# Changelog

A complete history of features, fixes, and improvements across every release.

"

COUNT=0
CURRENT_GROUP=""

while IFS= read -r tag; do
    COUNT=$((COUNT + 1))
    version="${tag#v}"

    # Extract major.minor group
    group_key=$(echo "$version" | sed 's/\.[^.]*$//')

    # If new group, add group heading
    if [ "$group_key" != "$CURRENT_GROUP" ]; then
        CURRENT_GROUP="$group_key"
        CHANGELOG_MD+="## Version $group_key

"
    fi

    # Get release date
    pub_date=$(echo "$RELEASES_JSON" | python3 -c "
import sys, json
releases = json.load(sys.stdin)
for r in releases:
    if r['tagName'] == '$tag':
        from datetime import datetime
        dt = datetime.fromisoformat(r['publishedAt'].replace('Z', '+00:00'))
        print(dt.strftime('%d %b %Y').lstrip('0'))
        break
")

    echo -e "  ${BLUE}[$COUNT/$TOTAL] $tag ($pub_date)${NC}"

    # Fetch release body
    body=$(gh release view "$tag" --repo "$REPO" --json body -q '.body' 2>/dev/null || echo "")

    # Extract changelog items
    items=$(extract_changelog_items "$body")

    # Add version heading with link and date
    CHANGELOG_MD+="### [$version]($REPO_URL/releases/tag/$tag) <Badge type=\"info\" text=\"$pub_date\" />

"

    if [ -z "$items" ]; then
        CHANGELOG_MD+="- Maintenance release

"
    else
        while IFS= read -r line; do
            [ -z "$line" ] && continue

            # Handle BREAKING: prefix
            if [[ "$line" == BREAKING:* ]]; then
                line="${line#BREAKING:}"
                # Add breaking badge before the line content
                line=$(echo "$line" | sed 's/^- /- **BREAKING** /')
            fi

            # Convert @username mentions to GitHub profile links (skip emails)
            line=$(echo "$line" | sed -E 's/(^|[^a-zA-Z0-9.@])@([a-zA-Z0-9_-]+)/\1[@\2](https:\/\/github.com\/\2)/g')

            CHANGELOG_MD+="$line
"
        done <<< "$items"
        CHANGELOG_MD+="
"
    fi
done <<< "$RELEASE_TAGS"

# Write the file
echo "$CHANGELOG_MD" > "$OUTPUT_FILE"

echo ""
echo -e "${GREEN}Changelog generated: $OUTPUT_FILE${NC}"
echo -e "${BLUE}Processed $COUNT releases${NC}"
