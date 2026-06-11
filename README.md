# Update Permissions Reminder

A Craft CMS 5 plugin that reminds admins to review and update user group
permissions whenever the schema changes in a way that introduces new
permissions.

When you add a new section, entry type, global set, asset volume, category
group, user group, or install a plugin, Craft does **not** automatically grant
the new permissions to your existing user groups. It's easy to ship a change and
forget that your editors now can't see the content you just built. This plugin
closes that gap with a gentle, dismissible reminder.

## What it does

- Watches for schema changes that add new permissions.
- Shows a **sticky bar** at the bottom of the control panel for admins, and/or a
  **modal** listing every outstanding change.
- Adds a **Permission Reminders** control panel section listing all pending
  reminders, each individually dismissible.
- Links straight to the user permission settings so you can act immediately.

Reminders are only shown to admins, since only admins can edit permissions.

## Requirements

- Craft CMS 5.0.0 or later
- PHP 8.2 or later

## Installation

From your project directory:

```bash
composer require justinholtweb/craft-update-permissions-reminder
php craft plugin/install update-permissions-reminder
```

Or install from the Plugin Store / Settings → Plugins in the control panel.

## Settings

Settings → Plugins → Update Permissions Reminder:

- **Show sticky reminder bar** — toggle the bottom bar.
- **Show reminder modal** — toggle the details modal.
- **Watched changes** — choose which of the following trigger reminders:
  sections, entry types, global sets, asset volumes, category groups, user
  groups, and installed plugins.

## How it works

The plugin listens to Craft's `afterSave` events for the relevant schema objects
and records a reminder when a new one is created. Each reminder is stored in
`updatepermissionsreminder_reminders` and cleared when an admin dismisses it.

## License

This plugin is licensed under [the Craft License](LICENSE.md).
