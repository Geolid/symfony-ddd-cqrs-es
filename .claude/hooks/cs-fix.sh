#!/bin/bash
# Runs the CS fixer on the edited PHP or Twig file only.
FILE=$(jq -r '.tool_input.file_path // empty' 2>/dev/null)
[ -z "$FILE" ] && exit 0
REL="${FILE#"$PWD"/}"
case "$FILE" in
    *.php) castor qa:cs --type=php --fix "$REL" ;;
    *.twig) castor qa:cs --type=twig --fix "$REL" ;;
esac
exit 0
