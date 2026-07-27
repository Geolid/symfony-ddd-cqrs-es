#!/bin/bash
# Blocks destructive or irreversible Bash commands.
INPUT=$(cat)
CMD=$(echo "$INPUT" | jq -r '.tool_input.command // empty')
[ -z "$CMD" ] && exit 0

RM_RECURSIVE='((^|[[:space:]])-[a-zA-Z]*r[a-zA-Z]*\b|--recursive\b)'
RM_FORCE='((^|[[:space:]])-[a-zA-Z]*f[a-zA-Z]*\b|--force\b)'

while IFS= read -r CLAUSE; do
  if echo "$CLAUSE" | grep -qiE '\brm\b' \
    && echo "$CLAUSE" | grep -qiE "$RM_RECURSIVE" \
    && echo "$CLAUSE" | grep -qiE "$RM_FORCE"; then
    echo "Blocked destructive command: $CMD" >&2
    exit 2
  fi
done <<< "$(echo "$CMD" | tr ';|&' '\n')"

if echo "$CMD" | grep -qiE 'git\s+reset\s+--hard|git\s+clean\s+-[a-z]*f|git\s+checkout\s+--\s|git\s+restore\b|git\s+push\b[^|;&]*(--force\b|-f\b)|git\s+branch\s+-[a-z]*d|git\s+stash\s+(drop|clear)'; then
  echo "Blocked destructive command: $CMD" >&2
  exit 2
fi

if echo "$CMD" | grep -qiE '\bdrop\s+(table|database)\b|\btruncate\b'; then
  echo "Blocked destructive command: $CMD" >&2
  exit 2
fi

exit 0
