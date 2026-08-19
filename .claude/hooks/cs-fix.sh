#!/bin/bash
# Runs the CS fixer on the edited PHP file only.
FILE=$(jq -r '.tool_input.file_path // empty' 2>/dev/null)
[ -z "$FILE" ] && exit 0
REL="${FILE#"$PWD"/}"
case "$FILE" in
    *.php) castor qa:cs --fix "$REL" ;;
esac
exit 0
