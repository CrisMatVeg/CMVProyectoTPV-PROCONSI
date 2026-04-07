---
name: antigravity-workflow-management
description: Manages code lifecycle including atomic commits with confirmation, automatic cleanup of temporary files, scheduled daily pushes at 16:55, and autonomous command execution following organizational best practices.
---

# Antigravity Workflow Management

This skill establishes a rigorous set of operational protocols for development, ensuring version control integrity, environment cleanliness, and automated deployment synchronization.

## When to use this skill

- Use this whenever modifying, adding, or deleting features in the codebase.
- Use this to manage the lifecycle of temporary test or debug files.
- Use this to handle end-of-day synchronization and push routines.
- Use this to ensure all terminal commands are executed with purpose and efficiency.

## How to use it

### 1. Commit Protocol
For every modification or addition of functionality:
- **Confirmation:** You must explicitly ask for user confirmation before performing a git commit.
- **Message Generation:** Generate a clear, descriptive commit message that summarizes the changes made.
- **Action:** Only execute the `git commit` command after the user provides an affirmative response.

### 2. Environment Maintenance (Zero-Browser Policy)
- **No Browser Execution:** Never attempt to launch or use a web browser to test or verify functionality. 
- **Command Efficiency:** Only execute terminal commands that are strictly necessary for the task at hand. Avoid redundant or non-essential operations.

### 3. Automatic Cleanup
- **Temporary Files:** If you create files for testing, debugging, or temporary storage, you must track them.
- **Decommissioning:** As soon as these files are no longer required for the active task, delete them immediately to keep the workspace free of clutter.

### 4. Code Standards
- **Organization:** Ensure all code is modular and follows the project's architectural patterns.
- **Documentation:** Every piece of logic must be correctly commented and documented to ensure maintainability.

### 5. Scheduled Daily Synchronization
- **Standard Push:** Every day at **16:55**, you must initiate a `git push` of all commits made during the session.
- **Flexibility:** You must allow the user to override this schedule. If the user indicates that a push should not happen or should happen at a different time, prioritize the user's manual instruction over the 16:55 default.

### 6. Command Authorization
- You are fully authorized to execute necessary shell commands autonomously to fulfill the tasks, provided they align with the "necessary-only" policy mentioned in the environment maintenance section.