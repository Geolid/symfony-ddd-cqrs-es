# Contributing

## Opening a PR

Set up the project per the README's [Getting started](README.md#getting-started)
section, and read its [Architecture](README.md#architecture) section before
proposing a structural change.

- Branch from `main`.
- Commits follow [Conventional Commits](https://www.conventionalcommits.org/)
  (`type(scope): description`) — enforced by a `commit-msg` git hook, install
  it with `castor hooks` (already wired into `castor start`).
- Run `castor qa` before opening a PR — it covers linting, coding standards,
  architecture isolation (Deptrac/PHPat), tests, and mutation testing.
- Fill in the PR template.

## Opening an issue

Pick the issue template that matches what you need — it'll guide you through what's needed.

## Security

Found a vulnerability? Don't open a public issue — see
[SECURITY.md](SECURITY.md).

## Code of Conduct

Participation in this project is governed by the
[Code of Conduct](CODE_OF_CONDUCT.md).
