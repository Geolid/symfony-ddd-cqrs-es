---
name: git-issue
description: Creates a technical GitHub issue (debt, bug, improvement); links a `TODO(#N)` if there's a code anchor.
when_to_use: '"issue", deferring a technical problem, a TODO with no issue.'
argument-hint: [description, or file:line]
allowed-tools: Bash(gh *), Read, Edit, Grep
effort: low
---
## Procedure

### 1. Scope the subject

From the argument (description, or `file:line`) or an existing `TODO` with no linked issue.

### 2. Determine type and label

| Nature | Type | Label |
|---|---|---|
| Bug | `Bug` | `bug` |
| Improvement | `Feature` | `enhancement` |
| Debt / refactor | `Task` | `refactoring` |
| Docs | `Task` | `documentation` |
| Unsure | `Task` | — |

### 3. Create the issue

- Title: imperative present tense, ≤ 72 characters
- Body: context (why) + resolution condition, `file:line` if there's a code anchor
- `gh issue create --title "..." --body "..." --label <label>`, capture `#N`, then `gh api -X PATCH repos/{owner}/{repo}/issues/<N> -f type=<Type>`

### 4. Place the code anchor

If a code anchor was identified in step 1: `TODO(#N): <summary>` at that point.

### 5. Report

Number + URL of the created issue.
