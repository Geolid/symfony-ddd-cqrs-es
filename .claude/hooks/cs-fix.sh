#!/bin/bash
# Runs the CS fixer on the edited PHP file only.
FILE=$(jq -r '.tool_input.file_path // empty' 2>/dev/null)
[ -z "$FILE" ] && exit 0
REL="${FILE#"$PWD"/}"
case "$FILE" in
    *.php) make cs-fix file="$REL" ;;
esac
exit 0
