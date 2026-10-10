# Project Guidelines & Automated Checks

## Formatting and Linting
- **Strict Trigger**: Do NOT run formatting, linting, or fix commands (`npm run format`, `composer lint`, `npm run lint`) during intermediate edits or regular conversational turns.
- Only run the automated check commands when:
  1. The user explicitly instructs to `"push"` or `"commit"`.
  2. The user explicitly asks to check or fix formatting/linting issues.
- **Quick Push Bypass**: When instructed with `"quick push"` or `"push direct"`, bypass all formatting and linting commands and proceed directly to commit/push.

```bash
npm run format && composer lint && npm run lint
```

## Git Workflow & Push
- **Strict Manual Trigger**: Never commit, stage, or push git changes automatically. Only perform commits or push when the user explicitly instructs to `"commit"` or `"push"` in that specific turn.
- When explicitly asked to push:
  1. Always create a new branch from `main` (pulling the latest changes first):
     ```bash
     git checkout main && git pull origin main && git checkout -b <new-branch>
     ```
  2. Switch to that branch.
  3. Use atomic commits where applicable.
  4. Run formatting and linting checks before committing (unless using "quick push"):
     ```bash
     npm run format && composer lint && npm run lint
     ```
  5. Never force push (`--force` or `-f`).
  6. Push the branch to the remote repository:
     ```bash
     git push -u origin <new-branch>
     ```
  7. Create a Pull Request (PR) with a clear, respective title and description linking relevant issues.
- **No CI Monitoring / Polling**: After pushing code or opening a PR, NEVER run `gh pr checks`, sleep loops, or monitor GitHub Actions CI in the background. Stop and respond immediately once the push and PR creation steps are finished.

## Strict Code Modification & Execution Guardrail
- **Explicit Instruction Required**: NEVER modify files, apply code edits, or execute code refactors unless the user explicitly gives direct instruction or confirmation to make the change (e.g., "do it", "apply this", "fix it", "proceed").
- **Exploratory / Question Turns**: When diagnosing bugs, answering questions, explaining behavior, or exploring solutions, provide explanations and code snippets in the response text ONLY. Do not apply file changes until confirmed.
