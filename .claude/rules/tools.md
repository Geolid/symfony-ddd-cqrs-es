---
paths:
  - "tools/**/*.php"
---

## Source

### Rules

**ALWAYS**
- A PHPat selector excludes `#/tests/#` — a test's own filepath mirrors the production one it targets and would otherwise produce false positives.
- `should()`/`canOnly()` with multiple target classes means ALL of them are required/allowed, not any one of them — read each rule's `because()` message before assuming the intent.
- `Selector::extends()` matches the full ancestor chain, not just the direct parent.
- A violation that looks inconsistent with the code right after an edit is usually a stale PHPStan result cache, not a real failure — re-run before investigating further.

### Conventions
- A new `*Test.php` file under `tools/PHPat/` is auto-discovered by its own `ArchitectureSuite` (glob + reflection) — no manual registration needed. Name a rule class after its subject, never after a vendor or a specific BC.
- A `because()` message states the bare why, naming the underlying principle only where useful — never restates the mechanical failure PHPStan's own output already shows.
- A PHPUnit runner extension is `<Concern>Extension implements Extension`, registering one or more subscribers via `registerSubscriber()`/`registerSubscribers()`; each subscriber is named `<Verb+Object>On<Event>` (reacting to a lifecycle event such as `TestSuite\Started` or `Test\PreparationStarted`), colocated in its own `<Concern>/` subfolder next to the Extension. A subscriber added for an already-covered concern joins that concern's own existing Extension instead of gaining a new one.
- A folder existing solely to hold one file of the exact same name adds no information over the file itself — it's promoted to its own parent's level instead, dropped entirely. A folder holding several differently-named files, or expected to gain siblings by its own established repo-wide shape, keeps its own level. That promotion only holds when the parent's own existing files already share the same role as the one being promoted — never when it would mix a distinct kind of file into a level whose contents are otherwise consistently one specific role; a lone file whose role genuinely differs from its would-be neighbors keeps its own folder instead, even alone.
