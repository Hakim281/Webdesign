# GitHub Submission Guide

The repository in this workspace does not currently have a GitHub remote configured.

## Recommended Steps

1. Create a new repository on GitHub.
2. Connect this local repository to GitHub:

```powershell
git remote add origin <your-github-url>
```

3. Check the files that will be submitted:

```powershell
git status
```

4. Stage the course submission:

```powershell
git add .gitignore BIT3208_Project
```

5. Create a commit:

```powershell
git commit -m "Prepare BIT3208 Week 4 and Week 5 submission"
```

6. Push the branch:

```powershell
git push -u origin <branch-name>
```

## Suggested Commit Messages

- `Create Week 4 PHP authentication project`
- `Add Week 4 contact form and database storage`
- `Add Week 5 CRUD student management system`
- `Organize weekly documentation and SQL backups`
