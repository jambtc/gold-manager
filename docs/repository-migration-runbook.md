# Repository Migration Runbook: gold-manager -> gold-manager-v2

This runbook operationalizes SIP-0072.

Goal: extract `v2/` from the monorepo and make it the root of a new repository, while preserving the Git history of the v2 application and restoring required root-level project assets such as docs and Docker files.

## Target repository

```text
jambtc/gold-manager-v2
```

## Source repository

```text
jambtc/gold-manager
```

Active source branch:

```text
gold-manager-v2
```

## What moves to the new repository

After migration, the new repository root should contain the former contents of `v2/` directly.

Examples:

```text
controllers/
models/
views/
components/
commands/
config/
web/
worker-go/
docs/
docker/
docker-compose.yml
start-queue.sh
```

The new repository should not require a `v2/` path prefix.

## Prerequisites

Install `git-filter-repo`.

macOS:

```bash
brew install git-filter-repo
```

Linux / pip:

```bash
python3 -m pip install --user git-filter-repo
```

Authenticate GitHub CLI:

```bash
gh auth login
```

## Step 0 - Pull latest source branch

Run this in your current working copy before starting the isolated migration clone:

```bash
git checkout gold-manager-v2
git pull origin gold-manager-v2
```

## Step 1 - Create isolated migration workspace

Use a fresh clone. Do not run `git filter-repo` inside your normal working directory.

```bash
rm -rf /tmp/gm-migration /tmp/gm-source

git clone https://github.com/jambtc/gold-manager.git /tmp/gm-source
cd /tmp/gm-source
git checkout gold-manager-v2
git pull origin gold-manager-v2

cp -a /tmp/gm-source /tmp/gm-migration
cd /tmp/gm-migration
```

## Step 2 - Extract v2 as repository root

```bash
git filter-repo --subdirectory-filter v2
```

After this step, paths that were under `v2/` become root-level paths.

Example:

```text
v2/controllers/SiteController.php
```

becomes:

```text
controllers/SiteController.php
```

## Step 3 - Restore root-level assets from the original repository

`git filter-repo --subdirectory-filter v2` removes files outside `v2/`, so restore the assets that are still part of the v2 project.

```bash
cp -a /tmp/gm-source/docs /tmp/gm-migration/
cp -a /tmp/gm-source/docker /tmp/gm-migration/
cp -a /tmp/gm-source/docker-compose.yml /tmp/gm-migration/

if [ -f /tmp/gm-source/start-queue.sh ]; then
  cp -a /tmp/gm-source/start-queue.sh /tmp/gm-migration/
fi

if [ -d /tmp/gm-source/.github ]; then
  cp -a /tmp/gm-source/.github /tmp/gm-migration/
fi

if [ -d /tmp/gm-source/.githooks ]; then
  cp -a /tmp/gm-source/.githooks /tmp/gm-migration/
fi
```

Commit the restored assets:

```bash
cd /tmp/gm-migration
git add docs docker docker-compose.yml

if [ -f start-queue.sh ]; then
  git add start-queue.sh
fi

if [ -d .github ]; then
  git add .github
fi

if [ -d .githooks ]; then
  git add .githooks
fi

git commit -m "chore: restore docs and infrastructure after v2 extraction"
```

## Step 4 - Rename branch to main

```bash
git branch -m main
```

## Step 5 - Create new GitHub repository and push

Create the repo as private first:

```bash
gh repo create jambtc/gold-manager-v2 --private --source=. --remote=origin --push
```

If the repository already exists, set the remote manually:

```bash
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/jambtc/gold-manager-v2.git
git push -u origin main
```

## Step 6 - Verify repository structure

Run:

```bash
ls -la
find . -maxdepth 2 -type d | sort | head -80
```

Expected:

- application folders at root;
- `docs/` at root;
- `worker-go/` at root;
- `docker-compose.yml` at root;
- no top-level `v2/` folder unless intentionally retained for compatibility.

## Step 7 - Find paths that still reference v2/

Run this in the new repository:

```bash
grep -R "v2/" -n . \
  --exclude-dir=.git \
  --exclude-dir=vendor \
  --exclude-dir=node_modules
```

Review results carefully. Some references inside historical SIPs are acceptable. Runtime references in scripts, Docker files, CI, or docs may need updates.

High-priority files to inspect:

```text
docker-compose.yml
docker/*
.github/*
.githooks/*
README.md
docs/README.md
docs/roadmap.md
docs/agent-working-notes.md
```

## Step 8 - Smoke test locally

Suggested commands:

```bash
composer install

if [ -d worker-go ]; then
  (cd worker-go && go test ./...)
fi

vendor/bin/codecept run Unit
```

If the Docker setup is expected to run locally:

```bash
docker compose config
docker compose up -d
```

Then verify the web entrypoint according to the local port configuration.

## Step 9 - Update deployment clone

Only after the new repo is verified.

Example:

```bash
mv /var/www/gold-manager /var/www/gold-manager-legacy

git clone https://github.com/jambtc/gold-manager-v2.git /var/www/gold-manager
cd /var/www/gold-manager
```

Then adjust environment files and Docker volumes if needed.

## Step 10 - Archive old repository

Only after the new repository is confirmed working.

```bash
gh repo archive jambtc/gold-manager
```

## Post-migration acceptance checklist

- [ ] New repository exists: `jambtc/gold-manager-v2`.
- [ ] Default branch is `main`.
- [ ] Former `v2/` contents are at repository root.
- [ ] `docs/` exists in the new repository.
- [ ] `docs/agent-working-notes.md` exists in the new repository.
- [ ] `docker-compose.yml` exists and is reviewed for obsolete `v2/` paths.
- [ ] Go worker tests compile or pass.
- [ ] Unit tests pass or known failures are documented.
- [ ] Deployment path points to the new repository.
- [ ] Old repository is archived only after verification.

## Notes

- `git filter-repo` rewrites history in place; always use an isolated clone.
- Historical SIPs may still mention `v2/`; this is acceptable when the reference is historical.
- Runtime scripts and deployment files should not assume `v2/` after migration unless a compatibility layer is intentionally kept.
