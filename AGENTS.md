# Project RouterOS — Agent Guidelines & Skills Protocol

This file configures and instructs AI coding agents (Antigravity, Claude Code, Cursor, Codex) working on `project-routeros`.

---

## 🎯 Active Skills & Intent Mapping

When working on tasks in this repository, map user requests to the installed **Agent Skills**:

| Development Phase | Intent / Trigger | Recommended Skill |
| :--- | :--- | :--- |
| **New Feature / Change** | "tambahkan fitur", "buatkan fungsi baru", spec drafting | `spec-driven-development` ➔ `incremental-implementation` |
| **Task Breakdown** | Complex tasks touching > 2 files | `planning-and-task-breakdown` |
| **Implementation** | Writing core PHP / Python / JS code | `test-driven-development` (TDD: Red-Green-Refactor) |
| **Refactoring** | "rapikan", "simplifikasi", redundant methods cleanup | `code-simplification` |
| **API & Interface Design** | Transports, public facade methods, REST endpoints | `api-and-interface-design` |
| **Bugs & Recovery** | Exception, connection failure, offline socket | `debugging-and-error-recovery` |
| **Pre-Commit Review** | Reviewing changes across 5 axes | `code-review-and-quality` (or subagent `code-reviewer`) |
| **Security Audit** | Credentials, password hashing, SQL injection, input sanitization | `security-and-hardening` (or subagent `security-auditor`) |
| **Deployment & Git** | Committing, tagging, pushing to GitHub | `git-workflow-and-versioning` ➔ `shipping-and-launch` |

---

## 🤖 Available Subagents

You can delegate specialized review and testing tasks to subagents:
- **`code-reviewer`**: Senior reviewer evaluating correctness, readability, architecture, and performance.
- **`security-auditor`**: Threat modeling, vulnerability scanning, and hardening.
- **`test-engineer`**: Quality assurance, test coverage analysis, and mock design.
- **`web-performance-auditor`**: Frontend and API latency optimization.

---

## 🛡️ Repository Constraints & Standards

1. **PHP Method Names**: PHP method names within the same class are **case-insensitive**. Never declare both `setName()` and `setname()`. Use camelCase for primary methods and forward aliases cleanly.
2. **Multi-Protocol Transports**: Keep `TransportInterface` contract intact. Any new connection method must support API, REST, SSH, and Telnet without breaking backward-compatibility.
3. **Multi-PTMP Routeros Connection**: PTMP modules must support legacy 802.11, NV2 TDMA, Nstreme, 60GHz, and WiFiWave2/WiFi 6.
4. **Documentation Integrity**: Maintain clickable `file://...` links in artifacts, preserve all existing docstrings, and update `example.php`, `console.php`, `api.php`, and `Readme.md` when adding public methods.
5. **Multi-Language Parity**: Features added to PHP Core should be mirrored in Python SDK (`routeros.py`) and JavaScript SDK (`assets/js/routeros.js`).
