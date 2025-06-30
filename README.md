# Guesty API Integration for WordPress

Integrate Guesty API with your WordPress site. Display properties, enable bookings, and manage API settings from the admin dashboard.

## Features
- Connect to Guesty API (all endpoints)
- Admin settings for API credentials, caching, and webhooks
- Property search, list, grid, and single views (with shortcodes)
- Booking form (no redirect)
- Translation ready
- Error handling and caching

## Installation
1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate via WordPress admin
3. Go to **Settings > Guesty API** to configure

## Shortcodes
- `[guesty_search]` — Property search form
- `[guesty_properties view="list|grid"]` — List or grid of properties
- `[guesty_property id="PROPERTY_ID"]` — Single property view
- `[guesty_booking id="PROPERTY_ID"]` — Booking form

## Development
- Logic in `/includes/`
- Admin UI in `/admin/`
- Templates in `/templates/`
- Styles in `/assets/css/`
- Translations in `/languages/`

## License
GPLv2 or later

