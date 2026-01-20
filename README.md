# CBP Change Freeze

A WordPress plugin that notifies logged-in users when a change freeze is in effect.

## Features

- **Simple Toggle**: Enable or disable the change freeze notification with one click
- **Custom Message**: Add your own message with HTML link support (perfect for Basecamp tickets)
- **Optional Date**: Include an informational date in the notification
- **Visual Indicators**:
  - Changes admin bar color to red when freeze is active
  - Displays prominent banner on the dashboard
  - Adds "CHANGE FREEZE" item to admin bar

## Installation

1. Upload the `cbp-change-freeze` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure settings at Settings → Change Freeze

## Usage

1. Go to **Settings → Change Freeze**
2. Check "Enable Change Freeze" to activate
3. Enter your custom message (e.g., "See Basecamp ticket: [link]")
4. Optionally add a date for reference
5. Save changes

When enabled, all logged-in users will see:

- Red admin bar (site-wide)
- Warning banner on the main dashboard
- "CHANGE FREEZE" notice in the admin bar

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher

## License

GPL v2 or later
