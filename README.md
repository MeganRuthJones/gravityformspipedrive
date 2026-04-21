# Gravity Forms Pipedrive Add-On

A Gravity Forms add-on that sends form submissions to Pipedrive CRM as Persons, Organizations, or Deals.

Built on the Gravity Forms Add-On Framework. Supports per-feed field mapping, conditional logic, and automatic deduplication (existing Persons are matched by email and updated; existing Organizations are matched by name).

## Requirements

- WordPress 5.5 or higher
- Gravity Forms 2.5 or higher
- A Pipedrive account with API access
- PHP 7.4 or higher

## Installation

1. Download the latest release as a zip (or zip the `gravityformspipedrive` folder yourself).
2. In WordPress admin, go to **Plugins → Add New → Upload Plugin**.
3. Upload the zip, install, and activate.
4. Go to **Forms → Settings → Pipedrive** and enter your Pipedrive API token.

### Finding your Pipedrive API token

In Pipedrive, go to **Settings → Personal preferences → API**. Copy the token and paste it into the add-on's settings page.

## Usage

1. Open a form in Gravity Forms.
2. Go to **Settings → Pipedrive** on that form.
3. Click **Add New** to create a feed.
4. Choose which Pipedrive object the feed should create — Person, Organization, or Deal.
5. Map form fields to Pipedrive fields.
6. (Optional) Add conditional logic to control when the feed runs.
7. Save.

When the form is submitted, the feed will run and push data to Pipedrive.

### How each object type behaves

**Person** — Searches Pipedrive for an existing person with an exact-matching email. If found, updates it with the submitted data. If not, creates a new person.

**Organization** — Searches for an existing organization with an exact-matching name. If found, uses that ID. If not, creates a new organization.

**Deal** — Always creates a new deal. If Person Email or Organization are mapped, the deal is linked to the matching (or newly-created) Person and Organization.

## Logging

This add-on uses the Gravity Forms logging framework. To see what it's doing:

1. Go to **Forms → Settings → Logging**.
2. Enable logging for the Pipedrive add-on at the **Log all messages** level.
3. Submit a test form entry.
4. View the log at **Forms → System Status → Logs**.

Logs will show the HTTP status, request URL, and response body for any failed Pipedrive calls.

## Known limitations

- Uses the Pipedrive v1 API. v1 is on Pipedrive's deprecation path; a v2 migration is planned.
- Authenticates via API token only. OAuth 2.0 is not yet supported.
- Supports Persons, Organizations, and Deals. Leads, Activities, and Notes are not yet supported.
- Does not yet support Pipedrive custom fields.

## Development

```
.
├── gravityformspipedrive.php       # Plugin bootstrap
├── class-gf-pipedrive-addon.php    # Main add-on class (extends GFFeedAddOn)
├── README.md
└── .gitignore
```

### Running locally

Clone the repo into your WordPress plugins directory:

```bash
cd wp-content/plugins
git clone <your-repo-url> gravityformspipedrive
```

Then activate the plugin from the WordPress admin.

## Changelog

### 1.0.1
- Fixed: extends `GFFeedAddOn` so feed settings and processing actually work
- Fixed: `wp_remote_put` (not a real function) replaced with `wp_remote_request`
- Fixed: request args no longer leak between Pipedrive API calls
- Improved: Person and Organization searches now use exact matching
- Improved: email and phone sent as arrays (Pipedrive API requirement)
- Improved: failed API calls now log HTTP status and response body

### 1.0.0
- Initial version.

## Author

Megan Jones

## License

GPL v2 or later (same as WordPress and Gravity Forms).
