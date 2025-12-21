# Analytics Plugin

The Analytics plugin provides a simple yet powerful way to track page visits and view reports within the Gaia Alpha admin panel.

## Features

- **Page Tracking**: Automatically records every unique page visit (ignoring API/Admin calls).
- **Dashboard**: A dedicated view in the admin panel showing:
    - Total Visits & Daily Visits.
    - Top 10 most visited pages.
    - Referral sources.
    - 30-day visit history chart.

## Implementation Details

### Database Schema

The plugin uses the `cms_analytics_visits` table:
- `page_path`: The URL path visited.
- `visitor_ip`: IP address of the visitor.
- `user_agent`: Browser identification string.
- `referrer`: The source page that led to the visit.
- `timestamp`: Date and time of the visit.

### Hooks

- `router_dispatch_after`: Used to trigger tracking after a route has been successfully matched and handled.

### API Endpoints

- `GET /@/analytics/stats`: Returns aggregated statistics (Admin only).

## Configuration

No special configuration is required. Simply enable the plugin, and it will start tracking visits immediately.
