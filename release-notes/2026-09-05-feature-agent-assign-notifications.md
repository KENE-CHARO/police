# Release: Agent assign & real-time notifications (2026-09-05)

Summary
- Allow `agent_accueil` to assign a `Plainte` to a real `enqueteur` via the UI.
- Backend: `POST /api/enquetes/assign` already supports `agent_accueil` assignments and creates a notification record for the enqueteur.
- Frontend: assignment selector populated from real users; after assign, local notification is added and `message` shown.
- Real-time: client now subscribes to `private('users.{id}')` via `window.Echo` (if configured) and pushes incoming `NotificationCreated` events into the UI.
- Added `tests/Feature/AgentAssignTest.php` to validate assign + notification.

Notes
- Ensure Laravel Echo / Pusher (or other broadcaster) is configured in the environment for live notifications.
- Test suite: run `php artisan test` to validate behavior.

Suggested PR description
"Enable agents to assign plaintes to enqueteurs and receive real-time assignment notifications. Includes frontend UI, tests, and backend notification integration."
