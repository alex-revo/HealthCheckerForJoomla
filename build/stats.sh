#!/bin/bash
#
# stats.sh - Display GitHub release download statistics
#
# Usage: ./build/stats.sh
#

set -e

REPO="mySites-guru/HealthCheckerForJoomla"

# Colors
BOLD='\033[1m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
CYAN='\033[0;36m'
RESET='\033[0m'

echo -e "${BOLD}📊 Health Checker for Joomla - Release Statistics${RESET}"
echo ""

# Get release data from GitHub API
releases=$(gh api repos/${REPO}/releases --jq '.[] | {
    tag: .tag_name,
    date: .published_at,
    pkg: (.assets[] | select(.name | startswith("pkg_")) | .download_count) // 0,
    com: (.assets[] | select(.name | startswith("com_")) | .download_count) // 0,
    mod: (.assets[] | select(.name | startswith("mod_")) | .download_count) // 0,
    core: (.assets[] | select(.name | startswith("plg_healthchecker_core")) | .download_count) // 0,
    akeeba_backup: (.assets[] | select(.name | startswith("plg_healthchecker_akeebabackup")) | .download_count) // 0,
    akeeba_admin: (.assets[] | select(.name | startswith("plg_healthchecker_akeebaadmintools")) | .download_count) // 0,
    mysites: (.assets[] | select(.name | startswith("plg_healthchecker_mysitesguru")) | .download_count) // 0,
    example: (.assets[] | select(.name | startswith("plg_healthchecker_example")) | .download_count) // 0
}')

# Calculate totals
total_pkg=0
total_com=0
total_mod=0
total_core=0
total_all=0

# Print header
printf "${BOLD}%-12s %-12s %8s %8s %8s %8s %8s %8s %8s %8s${RESET}\n" \
    "Release" "Date" "Package" "Comp" "Module" "Core" "Backup" "Admin" "mySites" "Example"
printf "%-12s %-12s %8s %8s %8s %8s %8s %8s %8s %8s\n" \
    "────────────" "────────────" "────────" "────────" "────────" "────────" "────────" "────────" "────────" "────────"

# Process each release
echo "$releases" | jq -r '[.tag, .date[0:10], .pkg, .com, .mod, .core, .akeeba_backup, .akeeba_admin, .mysites, .example] | @tsv' | \
while IFS=$'\t' read -r tag date pkg com mod core backup admin mysites example; do
    # Color the package count based on value
    if [ "$pkg" -gt 10 ]; then
        pkg_color="${GREEN}"
    elif [ "$pkg" -gt 0 ]; then
        pkg_color="${YELLOW}"
    else
        pkg_color="${RESET}"
    fi

    printf "%-12s %-12s ${pkg_color}%8s${RESET} %8s %8s %8s %8s %8s %8s %8s\n" \
        "$tag" "$date" "$pkg" "$com" "$mod" "$core" "$backup" "$admin" "$mysites" "$example"

    # Accumulate totals (write to temp file for subshell workaround)
    echo "$pkg $com $mod $core" >> /tmp/stats_totals_$$
done

# Read totals from temp file
if [ -f /tmp/stats_totals_$$ ]; then
    while read -r pkg com mod core; do
        total_pkg=$((total_pkg + pkg))
        total_com=$((total_com + com))
        total_mod=$((total_mod + mod))
        total_core=$((total_core + core))
    done < /tmp/stats_totals_$$
    rm -f /tmp/stats_totals_$$
fi

# Calculate totals directly from API for accuracy
totals=$(gh api repos/${REPO}/releases --jq '[.[].assets[]] | group_by(.name | split("-")[0]) | map({name: .[0].name | split("-")[0], total: map(.download_count) | add}) | .[]')

pkg_total=$(echo "$totals" | jq -r 'select(.name == "pkg_healthchecker") | .total')
com_total=$(echo "$totals" | jq -r 'select(.name == "com_healthchecker") | .total')
mod_total=$(echo "$totals" | jq -r 'select(.name == "mod_healthchecker") | .total')
core_total=$(echo "$totals" | jq -r 'select(.name == "plg_healthchecker_core") | .total')
backup_total=$(echo "$totals" | jq -r 'select(.name == "plg_healthchecker_akeebabackup") | .total')
admin_total=$(echo "$totals" | jq -r 'select(.name == "plg_healthchecker_akeebaadmintools") | .total')
mysites_total=$(echo "$totals" | jq -r 'select(.name == "plg_healthchecker_mysitesguru") | .total')
example_total=$(echo "$totals" | jq -r 'select(.name == "plg_healthchecker_example") | .total')

# Print totals
printf "%-12s %-12s %8s %8s %8s %8s %8s %8s %8s %8s\n" \
    "────────────" "────────────" "────────" "────────" "────────" "────────" "────────" "────────" "────────" "────────"
printf "${BOLD}%-12s %-12s ${CYAN}%8s${RESET} ${BOLD}%8s %8s %8s %8s %8s %8s %8s${RESET}\n" \
    "TOTAL" "" "${pkg_total:-0}" "${com_total:-0}" "${mod_total:-0}" "${core_total:-0}" "${backup_total:-0}" "${admin_total:-0}" "${mysites_total:-0}" "${example_total:-0}"

echo ""
echo -e "${BOLD}Summary:${RESET}"
echo -e "  📦 Total package downloads: ${CYAN}${pkg_total:-0}${RESET}"
echo -e "  🔢 Total releases: $(gh api repos/${REPO}/releases --jq 'length')"
echo -e "  ⭐ Repository: https://github.com/${REPO}"
echo ""
