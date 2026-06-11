# AGENTS.md — Update Permissions Reminder

## Project

A Craft CMS 5 plugin that reminds admins to review user group permissions after
schema changes that introduce new permissions.

- **Package**: `justinholtweb/craft-update-permissions-reminder`
- **Namespace**: `justinholtweb\updatepermissionsreminder`
- **Handle**: `update-permissions-reminder`
- **PHP**: 8.2+ | **Craft CMS**: ^5.0.0
- **License**: The Craft License (`LICENSE.md`)

## Architecture

- `Plugin` registers a `reminders` component, CP routes, the schema event
  listeners, and the CP UI injection.
- `services/Reminders` is split deliberately:
  - **Static, Craft-free half** — `changeTypes()`, `label()`,
    `permissionUrl()`, `messageFor()`, `isWatched()`, `watchedTypes()`. This is
    the unit-tested logic; it must never touch the Craft container.
  - **Instance half** — `record()`, `getPending()`, `dismiss()`,
    `dismissAll()`, persisting to `updatepermissionsreminder_reminders`.
- Reminders are recorded from `afterSave*` / `afterInstallPlugin` events when
  `isNew` and the change type is enabled in settings.
- The sticky bar + modal are injected via `View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE`
  (CP requests, admins only, when reminders are pending). `ReminderAsset` ships
  plain CSS/JS — no build step. JS renders the bar/modal and dismisses via
  fetch POST to `RemindersController`.

## Change types

`section`, `entryType`, `globalSet`, `volume`, `categoryGroup`, `userGroup`,
`plugin` — each maps 1:1 to a `watch*` flag in `models/Settings` and a row in
`Reminders::DEFINITIONS`.

## Conventions

- PHP 8.2+, PSR-4 under `src/`, `// region` markers in larger classes.
- Dynamic values in JS are escaped (`escape()` / `attr()`) before innerHTML.
- Only admins see or manage reminders.

## Tests

`composer test` (PHPUnit). Tests live in `tests/unit` and cover the static half
of `Reminders` only — no Craft bootstrap required.

## File layout

```
src/
├── Plugin.php
├── icon.svg
├── models/{Settings,Reminder}.php
├── records/ReminderRecord.php
├── services/Reminders.php
├── controllers/RemindersController.php
├── migrations/Install.php
├── web/assets/reminder/{ReminderAsset.php,dist/reminder.css,dist/reminder.js}
├── templates/{settings,_index}.twig
└── translations/en/update-permissions-reminder.php
```
