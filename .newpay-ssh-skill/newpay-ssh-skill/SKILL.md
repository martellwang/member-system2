---
name: newpay-ssh-skill
description: Deploy the NewPay PHP member system and its current database to the configured remote member site over SSH/SFTP, with a remote database backup, UTF-8-safe archive transfer, minimal post-deploy checks, and rollback guidance. Use when the user asks to push, publish, deploy, or synchronize the current NewPay project to www.newpay.com.tw/member.
---

# NewPay SSH Deployment

Use this skill for routine deployment of the current local NewPay project to the production member site. Keep the process repeatable, backup-first, and UTF-8 safe.

## Fixed Target

- SSH host: `www.newpay.com.tw`
- SSH account: use the credential supplied in the current conversation or environment; never write or echo the password.
- Remote web root: `/var/www/clients/client1/web1/home/newpay_web/web/member`
- Remote database: `newpay_member` on `localhost`
- Local project: the current workspace
- Local XAMPP mirror: `C:\xampp\htdocs\member-system2`
- Local database: `member_system`

Do not deploy to the parent web directory. The target is specifically the `member` subdirectory.

## Standard Workflow

1. Inspect `git status --short` and preserve unrelated user changes. Do not reset or clean the worktree.
2. Sync the current project to the XAMPP mirror when the task concerns the local site. Use `robocopy` for file copies and treat exit code 0 or 1 as success.
3. Export the local database as UTF-8/UTF-8MB4 SQL with `mysqldump` from `C:\xampp\mysql\bin`, including routines and triggers when available. Store the dump in a temporary or ignored `database\local-member-system-*.sql` path.
4. Build a tar.gz archive of the project using forward-slash archive paths. Exclude `.git`, `.env`, local dumps, caches, logs, and temporary packages. Do not use a Windows zip archive with backslashes for the remote extraction step.
5. Connect with SSH/SFTP. On this Windows machine, invoke Windows PowerShell with `-NoProfile -ExecutionPolicy Bypass` when importing Posh-SSH. Use connection and operation timeouts of at least 60 seconds.
6. Upload the tar.gz and SQL dump to `/tmp/` using unique filenames. Upload a temporary MySQL defaults file only for the remote command and remove it immediately afterward.
7. Before replacing files, create a timestamped remote database backup with `mysqldump`.
8. Extract into a unique `/tmp/member-system2-work-*` directory, copy its contents into the exact target directory, and import the SQL dump into `newpay_member` using UTF-8MB4 settings.
9. Apply normal permissions: directories `755`, files `644`, and application storage writable only as required by the existing app. Never expose the database credentials in output or committed files.
10. Remove uploaded archives, SQL dumps, temporary credential files, and work directories after success. Keep the remote database backup path in the deployment report.

## Minimal Verification

Do not repeat a full test suite or re-test every page for routine pushes. After a successful deployment, perform only:

- HTTP status check for `https://www.newpay.com.tw/member` and confirm `200`.
- A lightweight remote database count query for key tables, such as `members` and `member_stores`, when a database import was requested.
- Confirm the deployment command reports the exact target path and backup path.

If the HTTP check fails, inspect the remote PHP/web error log and report the failure; do not silently retry an overwrite. If the SQL import fails, preserve the remote backup and report the exact error before attempting rollback.

## Credential and Data Rules

- Never place SSH, database, Gmail, or app-password values in this skill, Git, logs, shell history, or the final response.
- Treat the local database as user-authorized deployment data only when the user explicitly asks to push database data.
- Always back up the remote database before importing local data.
- Use UTF-8MB4 for dumps, connections, and imports. Keep source files UTF-8.
- Prefer an atomic staging directory where practical; do not delete the existing target before a successful archive extraction.

## Common Windows Issue

If Posh-SSH fails because Windows PowerShell blocks module scripts, retry with:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "Import-Module Posh-SSH"
```

This does not require reinstalling PowerShell. PowerShell 7 may be installed side-by-side for better UTF-8 and module behavior, but it is optional for this deployment workflow.
