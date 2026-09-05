# Project Guidelines & Automated Checks

## Formatting and Linting
When asked to fix or check formatting/linting issues, or before committing code changes, always run the automated fix commands directly instead of manually inspecting and fixing errors one by one:

```bash
npm run format && composer lint && npm run lint
```

## Git Workflow & Push
When asked to push:
1. Always create a new branch from `main` (pulling the latest changes first):
   ```bash
   git checkout main && git pull origin main && git checkout -b <new-branch>
   ```
2. Switch to that branch.
3. Use atomic commits where applicable.
4. Never force push (`--force` or `-f`).
5. Push the branch to the remote:
   ```bash
   git push -u origin <new-branch>
   ```

