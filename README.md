# GD Audit

GD Audit is a lightweight WordPress plugin that records key user actions, surfaces them in a searchable dashboard, and helps site owners track changes.

## Features
- Tracks post status changes, user profile edits, registrations, deletions, and logins.
- Stores audit records in a dedicated custom table to avoid bloating core logs.
- Includes an admin page under `Tools → GD Audit` with filters for event type, date range, and keyword search.
- Supports pagination preferences per user.
- Shows contextual metadata (post type, user roles, etc.) in expandable sections.

## Installation
1. Copy the plugin folder `gd-audit` into your WordPress installation under `wp-content/plugins/`.
2. Activate **GD Audit** from the WordPress Admin Plugins page.
3. On activation, the plugin automatically creates the `wp_gd_audit_logs` table.

## Usage
1. Navigate to `Tools → GD Audit`.
2. Use the filters at the top to narrow down events.
3. Expand any log row to view the stored context JSON.

## Development
- Minimum WordPress version: 6.0
- Minimum PHP version: 7.4
- Version: 1.0.0

To modify, edit the PHP classes under `includes/` or the CSS under `assets/css/admin.css`. After changes, reinstall or re-activate the plugin to ensure database schema updates run via activation hook.

## Uninstalling
Deactivating the plugin keeps historical logs. To remove the table entirely, run:
```sql
DROP TABLE IF EXISTS wp_gd_audit_logs;
```
(replace `wp_` with your WordPress table prefix if different.)
