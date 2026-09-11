# Project Guidelines & Automated Checks

## Formatting and Linting
When asked to fix or check formatting/linting issues, or before committing changes, run the automated fix commands directly instead of manually inspecting and fixing errors one by one:

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
  4. Always run formatting and linting checks before committing:
     ```bash
     npm run format && composer lint && npm run lint
     ```
  5. Never force push (`--force` or `-f`).
  6. Push the branch to the remote repository:
     ```bash
     git push -u origin <new-branch>
     ```
  7. Create a Pull Request (PR) with a clear, respective title and description linking relevant issues.
