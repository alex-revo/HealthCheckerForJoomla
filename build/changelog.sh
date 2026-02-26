#!/bin/bash

# Health Checker for Joomla - Changelog Generator
# Fetches GitHub releases and generates a static HTML changelog page

set -e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
OUTPUT_DIR="$PROJECT_ROOT/website/public/changelog"
OUTPUT_FILE="$OUTPUT_DIR/index.html"
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

# Create output directory
mkdir -p "$OUTPUT_DIR"

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
# Returns: changelog lines (one per line), or empty if none found
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

# Convert a single changelog line to HTML
line_to_html() {
    local line="$1"
    local is_breaking=false

    # Check for breaking prefix
    if [[ "$line" == BREAKING:* ]]; then
        is_breaking=true
        line="${line#BREAKING:}"
    fi

    # Use python for reliable parsing
    echo "$line" | python3 -c "
import sys, re, html

line = sys.stdin.read().strip()
is_breaking = $( [[ $is_breaking == true ]] && echo 'True' || echo 'False' )

# Remove leading '- '
if line.startswith('- '):
    line = line[2:]

# Detect badge type
badge = ''
badge_class = ''
rest = line

# Match [Type] prefix
m = re.match(r'^\[([A-Za-z]+)\]\s*(.*)', line)
if m:
    tag = m.group(1)
    rest = m.group(2)
    tag_lower = tag.lower()
    if tag_lower == 'feature':
        badge = 'Feature'
        badge_class = 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
    elif tag_lower == 'fix':
        badge = 'Fix'
        badge_class = 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300'
    elif tag_lower == 'security':
        badge = 'Security'
        badge_class = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
    elif tag_lower == 'performance':
        badge = 'Performance'
        badge_class = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
    elif tag_lower in ('internal', 'release', 'info'):
        badge = tag.capitalize()
        badge_class = 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'
    else:
        badge = tag.capitalize()
        badge_class = 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'

# Escape HTML in the rest
rest = html.escape(rest)

# Convert markdown links [text](url) first
rest = re.sub(
    r'\[([^\]]+)\]\(([^)]+)\)',
    r'<a href=\"\2\" class=\"text-joomla-link hover:underline\">\1</a>',
    rest
)

# Convert bare issue/PR references (#N) or #N that aren't already inside <a> tags
rest = re.sub(
    r'(?<![\w\"/])#(\d+)(?![^<]*</a>)',
    r'<a href=\"https://github.com/mySites-guru/HealthCheckerForJoomla/issues/\1\" class=\"text-joomla-link hover:underline\">#\1</a>',
    rest
)

# Convert (Thanks @username) to linked version
rest = re.sub(
    r'\(Thanks @(\w+)\)',
    r'(Thanks <a href=\"https://github.com/\1\" class=\"text-joomla-link hover:underline\">@\1</a>)',
    rest
)

# Build the HTML
parts = []
parts.append('<li class=\"flex items-start gap-2 py-1\">')

if is_breaking:
    parts.append('<span class=\"inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 shrink-0 mt-0.5\">Breaking</span>')

if badge:
    parts.append(f'<span class=\"inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {badge_class} shrink-0 mt-0.5\">{badge}</span>')

parts.append(f'<span>{rest}</span>')
parts.append('</li>')

print(''.join(parts))
"
}

# Build the HTML content for all releases by processing each tag sequentially
# Group by major.minor using a temp file approach to avoid bash associative array issues
echo -e "${YELLOW}Processing releases...${NC}"

CHANGELOG_HTML=""
COUNT=0
CURRENT_GROUP=""

while IFS= read -r tag; do
    COUNT=$((COUNT + 1))
    version="${tag#v}"

    # Extract major.minor group
    group_key=$(echo "$version" | sed 's/\.[^.]*$//')

    # If new group, close previous and open new
    if [ "$group_key" != "$CURRENT_GROUP" ]; then
        if [ -n "$CURRENT_GROUP" ]; then
            CHANGELOG_HTML+="
                </div>"
        fi
        CURRENT_GROUP="$group_key"
        CHANGELOG_HTML+="
                <div class=\"mb-12\">
                    <h2 class=\"text-2xl font-bold text-gray-900 dark:text-white mb-6 pb-2 border-b border-gray-200 dark:border-gray-700\">Version $group_key</h2>"
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

    # Build items HTML
    ITEMS_HTML=""
    if [ -z "$items" ]; then
        ITEMS_HTML="<li class=\"flex items-start gap-2 py-1\"><span class=\"inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 shrink-0 mt-0.5\">Release</span><span>Maintenance release</span></li>"
    else
        while IFS= read -r line; do
            [ -z "$line" ] && continue
            item_html=$(line_to_html "$line")
            ITEMS_HTML+="$item_html
"
        done <<< "$items"
    fi

    CHANGELOG_HTML+="
                    <div class=\"mb-8\">
                        <div class=\"flex items-baseline gap-3 mb-3\">
                            <h3 class=\"text-lg font-semibold text-gray-900 dark:text-white\">
                                <a href=\"$REPO_URL/releases/tag/$tag\" class=\"hover:text-joomla-link transition-colors\">$version</a>
                            </h3>
                            <time class=\"text-sm text-gray-500 dark:text-gray-400\">$pub_date</time>
                        </div>
                        <ul class=\"space-y-1 text-sm text-gray-700 dark:text-gray-300 list-none pl-0\">
                            $ITEMS_HTML
                        </ul>
                    </div>"
done <<< "$RELEASE_TAGS"

# Close last group
if [ -n "$CURRENT_GROUP" ]; then
    CHANGELOG_HTML+="
                </div>"
fi

# Get latest version for download button
LATEST_TAG=$(echo "$RELEASE_TAGS" | head -1)
LATEST_VERSION="${LATEST_TAG#v}"

echo ""
echo -e "${YELLOW}Generating HTML...${NC}"

cat > "$OUTPUT_FILE" << 'HTMLHEAD'
<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Changelog - Health Checker for Joomla</title>
        <meta
            name="description"
            content="Complete changelog for Health Checker for Joomla. See all features, fixes, and improvements across every release."
        />
        <meta
            name="robots"
            content="index, follow"
        />
        <meta name="author" content="Phil E. Taylor, mySites.guru" />
        <link rel="canonical" href="https://www.joomlahealthchecker.com/changelog/" />

        <!-- Open Graph -->
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://www.joomlahealthchecker.com/changelog/" />
        <meta property="og:title" content="Changelog - Health Checker for Joomla" />
        <meta property="og:description" content="Complete changelog for Health Checker for Joomla. See all features, fixes, and improvements across every release." />
        <meta property="og:site_name" content="Health Checker for Joomla" />

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary" />
        <meta name="twitter:title" content="Changelog - Health Checker for Joomla" />
        <meta name="twitter:description" content="Complete changelog for Health Checker for Joomla. See all features, fixes, and improvements across every release." />

        <link rel="icon" type="image/png" href="https://www.joomlahealthchecker.com/jhc-favicon-96x96.png?v=3" sizes="96x96" />
        <link rel="icon" type="image/svg+xml" href="https://www.joomlahealthchecker.com/jhc-favicon.svg?v=3" />
        <link rel="shortcut icon" href="https://www.joomlahealthchecker.com/jhc-favicon.ico?v=3" />
        <link rel="apple-touch-icon" sizes="180x180" href="https://www.joomlahealthchecker.com/jhc-apple-touch-icon.png?v=3" />

        <link rel="stylesheet" href="/output.css?v=2" />
        <script>
            (function () {
                const prefersDark = window.matchMedia(
                    "(prefers-color-scheme: dark)",
                ).matches;
                if (prefersDark) {
                    document.documentElement.classList.add("dark");
                }
            })();
        </script>
    </head>
    <body
        class="font-sans text-gray-700 bg-white dark:bg-gray-900 dark:text-gray-100 leading-relaxed"
    >
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-joomla-primary focus:text-white focus:rounded-md"
            >Skip to main content</a
        >

        <header
            class="py-4 md:py-5 border-b border-gray-200 dark:border-gray-700"
        >
            <div class="max-w-6xl mx-auto px-4 md:px-6">
                <div class="flex justify-between items-center">
                    <a
                        href="/"
                        class="flex items-center gap-2 text-base md:text-lg font-semibold text-joomla-primary no-underline shrink-0"
                        aria-label="Joomla Health Checker - Home"
                    >
                        <img
                            src="/logo.svg"
                            alt="Health Checker for Joomla logo"
                            class="w-7 h-7 md:w-8 md:h-8"
                        />
                        <span class="hidden sm:inline"
                            >Health Checker for Joomla</span
                        >
                        <span class="sm:hidden">Health Checker</span>
                    </a>
                    <button
                        type="button"
                        class="md:hidden p-2 text-joomla-primary dark:text-blue-400"
                        onclick="
                            document
                                .getElementById('mobile-menu')
                                .classList.toggle('hidden')
                        "
                        aria-label="Toggle menu"
                    >
                        <svg
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            ></path>
                        </svg>
                    </button>
                    <div class="hidden md:flex items-center gap-6">
                        <a
                            href="/docs/"
                            class="inline-flex items-center justify-center py-2.5 text-joomla-primary font-medium text-sm no-underline hover:text-joomla-secondary transition-colors"
                            >Documentation</a
                        >
                        <a
                            href="/docs/developers/"
                            class="inline-flex items-center justify-center py-2.5 text-joomla-primary font-medium text-sm no-underline hover:text-joomla-secondary transition-colors"
                            >Extend</a
                        >
                        <a
                            href="/changelog/"
                            class="inline-flex items-center justify-center py-2.5 text-joomla-primary font-medium text-sm no-underline hover:text-joomla-secondary transition-colors border-b-2 border-joomla-primary"
                            >Changelog</a
                        >
                        <a
                            href="https://github.com/mySites-guru/HealthCheckerForJoomla/issues"
                            class="inline-flex items-center justify-center py-2.5 text-joomla-primary font-medium text-sm no-underline hover:text-joomla-secondary transition-colors"
                            >Support</a
                        >
                        <a
                            href="https://github.com/mySites-guru/HealthCheckerForJoomla"
                            class="inline-flex items-center justify-center p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors"
                            aria-label="View on GitHub"
                            title="View on GitHub"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"
                                    clip-rule="evenodd"
                                ></path>
                            </svg>
                        </a>
HTMLHEAD

# Inject the download button with the latest version dynamically
cat >> "$OUTPUT_FILE" << HTMLDOWNLOAD
                        <a
                            href="https://github.com/mySites-guru/HealthCheckerForJoomla/releases/download/v${LATEST_VERSION}/pkg_healthchecker-${LATEST_VERSION}.zip"
                            class="inline-flex items-center justify-center px-5 py-2.5 bg-joomla-primary text-white rounded-md font-medium text-sm no-underline hover:bg-joomla-secondary transition-colors dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100"
                            >Download Free</a
                        >
                    </div>
                </div>
                <div
                    id="mobile-menu"
                    class="hidden md:hidden mt-4 pb-2 border-t border-gray-100 pt-4"
                >
                    <div class="flex flex-col gap-3">
                        <a
                            href="/docs/"
                            class="py-2 text-joomla-primary font-medium text-sm no-underline hover:text-joomla-secondary transition-colors"
                            >Documentation</a
                        >
                        <a
                            href="/docs/developers/"
                            class="py-2 text-joomla-primary font-medium text-sm no-underline hover:text-joomla-secondary transition-colors"
                            >Extend</a
                        >
                        <a
                            href="/changelog/"
                            class="py-2 text-joomla-primary font-medium text-sm no-underline hover:text-joomla-secondary transition-colors"
                            >Changelog</a
                        >
                        <a
                            href="https://github.com/mySites-guru/HealthCheckerForJoomla/issues"
                            class="py-2 text-joomla-primary font-medium text-sm no-underline hover:text-joomla-secondary transition-colors"
                            >Support</a
                        >
                        <a
                            href="https://github.com/mySites-guru/HealthCheckerForJoomla"
                            class="inline-flex items-center gap-2 py-2 text-gray-600 font-medium text-sm no-underline hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"
                                    clip-rule="evenodd"
                                ></path>
                            </svg>
                            GitHub
                        </a>
                        <a
                            href="https://github.com/mySites-guru/HealthCheckerForJoomla/releases/download/v${LATEST_VERSION}/pkg_healthchecker-${LATEST_VERSION}.zip"
                            class="inline-flex items-center justify-center px-5 py-2.5 bg-joomla-primary text-white rounded-md font-medium text-sm no-underline hover:bg-joomla-secondary transition-colors dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100"
                            >Download Free</a
                        >
                    </div>
                </div>
            </div>
        </header>
HTMLDOWNLOAD

# Main content
cat >> "$OUTPUT_FILE" << 'HTMLMAIN'

        <main id="main-content" class="py-12 md:py-16">
            <div class="max-w-4xl mx-auto px-4 md:px-6">
                <div class="mb-10">
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-3">Changelog</h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">A complete history of features, fixes, and improvements across every release.</p>
                </div>

HTMLMAIN

# Inject the changelog content
echo "$CHANGELOG_HTML" >> "$OUTPUT_FILE"

# Close main and add footer
cat >> "$OUTPUT_FILE" << 'HTMLFOOTER'
            </div>
        </main>

        <footer class="py-6">
            <div class="max-w-6xl mx-auto px-6">
                <div
                    class="flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-gray-500 dark:text-gray-500"
                >
                    <p>
                        &copy; 2026
                        <a
                            href="https://www.phil-taylor.com"
                            target="_blank"
                            class="text-gray-500 hover:text-joomla-link transition-colors"
                            rel="noopener noreferrer"
                        >
                            Phil E. Taylor
                        </a>
                        /
                        <a
                            href="https://mySites.guru"
                            target="_blank"
                            class="text-gray-500 hover:text-joomla-link transition-colors"
                            title="Manage multiple Joomla sites from one console"
                            rel="noopener noreferrer"
                        >
                            mySites.guru
                        </a>
                    </p>
                    <ul class="flex gap-6 list-none">
                        <li>
                            <a
                                href="/docs/getting-started.html"
                                class="text-gray-500 hover:text-joomla-link transition-colors"
                                >Documentation</a
                            >
                        </li>
                        <li>
                            <a
                                href="/docs/developers/"
                                class="text-gray-500 hover:text-joomla-link transition-colors"
                                >Developer Guide</a
                            >
                        </li>
                        <li>
                            <a
                                href="https://github.com/mySites-guru/HealthCheckerForJoomla/issues"
                                class="text-gray-500 hover:text-joomla-link transition-colors"
                                >Support</a
                            >
                        </li>
                    </ul>
                </div>
                <p class="text-xs text-gray-400 text-center mt-6">
                    This site is not affiliated with or endorsed by the Joomla!
                    Project. It is not supported or warranted by the Joomla!
                    Project or Open Source Matters.
                </p>
            </div>
        </footer>
    </body>
</html>
HTMLFOOTER

echo ""
echo -e "${GREEN}Changelog generated: $OUTPUT_FILE${NC}"
echo -e "${BLUE}Processed $COUNT releases${NC}"
