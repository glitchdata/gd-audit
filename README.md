# GD Audit

GD Audit is a lightweight WordPress plugin that records key user actions, surfaces them in a searchable dashboard, and helps site owners track changes.

## Features
- Tracks post status changes, user profile edits, registrations, deletions, and logins.
- Stores audit records in a dedicated custom table to avoid bloating core logs.
- Adds a dedicated top-level `GD Audit` admin menu with Dashboard, Logs, and Settings tabs.
- Dashboard tab surfaces post analytics (status totals, daily publish trend, top authors, and recent publications).
- Plugins tab lists every installed plugin with activation status and update availability signals.
- Themes tab inventories installed themes, highlighting the active/child themes and pending updates.
- Users tab surfaces registration trends, role distribution, and the latest signups.
- Allows enabling/disabling individual event types, enforcing retention windows, and masking IP addresses.
- Supports pagination preferences per user and shows contextual metadata (post type, user roles, etc.) in expandable sections.

## Installation
1. Copy the plugin folder `gd-audit` into your WordPress installation under `wp-content/plugins/`.
2. Activate **GD Audit** from the WordPress Admin Plugins page.
3. On activation, the plugin automatically creates the `wp_gd_audit_logs` table.

## Usage
1. Navigate to `GD Audit → Dashboard` to review the latest post analytics.
2. Use `GD Audit → Plugins` to audit installed extensions, see which are active, and spot pending updates.
3. Visit `GD Audit → Themes` to check which themes are installed, identify child themes, and review update availability.
4. Review `GD Audit → Users` for registration trends, role distribution, and quick access to recent profiles.
5. Switch to `GD Audit → Logs` to review the event stream and apply filters.
6. Expand any log row to view the stored context JSON.
7. Configure capture rules, retention, and privacy controls under `GD Audit → Settings`.

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
