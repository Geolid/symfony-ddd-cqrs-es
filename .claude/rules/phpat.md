---
paths:
  - "tools/PHPat/*Test.php"
---

## Source

### Rules

**ALWAYS**
- A selector excludes `#/tests/#` — a test's own filepath mirrors the production one it targets and would otherwise produce false positives.
- `should()`/`canOnly()` with multiple target classes means ALL of them are required/allowed, not any one of them — read each rule's `because()` message before assuming the intent.
- `Selector::extends()` matches the full ancestor chain, not just the direct parent.
- A violation that looks inconsistent with the code right after an edit is usually a stale PHPStan result cache, not a real failure — re-run before investigating further.

### Conventions
- A new `*Test.php` file under `tools/PHPat/` is auto-discovered by `Tools\PHPat\ArchitectureSuite` (glob + reflection) — no manual registration needed.
- Name a rule class after its subject, never after a vendor or a specific BC.
