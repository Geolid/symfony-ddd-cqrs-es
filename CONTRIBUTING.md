# Contributing

## Getting started

`make start` boots the whole stack, installs dependencies, and seeds demo
data — see the README's [Getting started](README.md#getting-started) section.

## Workflow

- Branch from `main`.
- Commits follow [Conventional Commits](https://www.conventionalcommits.org/)
  (`type(scope): description`) — enforced by a `commit-msg` git hook, install
  it with `make hooks` (already wired into `make start`).
- Run `make qa` before opening a PR — it covers linting, coding standards,
  architecture isolation (Deptrac/PHPat), tests, and mutation testing.
- Fill in the PR template.

## Architecture

This is a DDD/CQRS/Event Sourcing showcase — Bounded Context isolation and
Onion layering are enforced by static analysis (Deptrac/PHPat), not just
convention. Read the README's [Architecture](README.md#architecture) section
before proposing a change that crosses a Bounded Context boundary.

## Reporting a bug or requesting a feature

Use the issue templates — they'll guide you through what's needed.

## Security

Found a vulnerability? Don't open a public issue — see
[SECURITY.md](SECURITY.md).

## Code of Conduct

Participation in this project is governed by the
[Code of Conduct](CODE_OF_CONDUCT.md).
