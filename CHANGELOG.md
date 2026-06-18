# Release Notes for Update Permissions Reminder

## 5.0.1 - 2026-06-18

### Added
- Icon mask for control panel sidebar usage.

## 5.0.0 - 2026-06-11

### Added
- Initial release of Update Permissions Reminder for Craft CMS 5.
- Detects schema changes that introduce new user permissions: new sections, entry types, global sets, asset volumes, category groups, user groups, and installed plugins.
- Sticky reminder bar in the control panel prompting admins to review user group permissions.
- Optional modal listing every outstanding change since permissions were last reviewed.
- Control panel section listing all pending reminders with one-click dismissal.
- Per-change-type settings to control which schema events trigger reminders.
- Settings to toggle the sticky bar and modal independently.
- Reminders are shown only to admins, who are the users able to edit permissions.
- PHPUnit unit test suite covering reminder message generation, permission-screen routing, and watched-type filtering.
