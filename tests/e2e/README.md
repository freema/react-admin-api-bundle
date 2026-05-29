# End-to-end testing with the chrome-devtools MCP

This directory holds end-to-end runbooks that exercise the bundle through a real browser via the Claude Code `chrome-devtools` MCP server. They're written as numbered, replayable steps so they can be driven either manually or by an automation agent.

## Prerequisites

- A Claude Code session with the `chrome-devtools` MCP server connected.
- A demo host application (Symfony backend on `localhost:8080`, Vite admin app on `localhost:5173`). The bundle does not bundle a demo app — point the runbooks at whichever consumer you're validating.
- Optional: `task` (https://taskfile.dev) for orchestration.

## Available runbooks

| File | Scope |
|---|---|
| `crud-flow.md` | Full users CRUD round-trip with assertions on Content-Range header, optimistic concurrency (ETag), and Suspense fallbacks. |

## Running

The MCP server exposes tools like `mcp__chrome-devtools__navigate_page`, `take_snapshot`, `evaluate_script`, `click`, `fill_form`, `list_network_requests`, `list_console_messages`. Each step in a runbook maps 1:1 to a tool call — replay them sequentially.

For automated runs, wrap the tool calls in a Claude Code session and let the agent drive them. The runbooks assume an admin user is already logged in (cookie present) — handle authentication outside the script or via a dedicated `01-login.md` runbook.

## Authoring conventions

- Steps numbered 1..N with no gaps.
- Every `evaluate_script` ends with a `→` line describing the expected return value.
- `wait_for` uses text content, never timing waits (more resilient against latency variance).
- Each runbook ends with `list_console_messages → 0 errors`.
- Cleanup steps live at the end and are mandatory — never leave persistent state behind.
