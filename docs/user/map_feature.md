# Map Feature Documentation

## Overview
The Map feature allows users to view a collaborative map and add markers to it. It uses Leaflet.js for the map interface and stores markers in the SQLite database.

## Features
- **Interactive Map**: Users can view a map centered on a default location.
- **Marker Creation**: Users can click anywhere on the map to add a labeled marker.
- **Marker Updates**: Users can drag existing markers to update their position.
- **Persistence**: Markers are saved to the backend and persist across sessions.
- **Lazy Loading**: The Leaflet library (CSS and JS) is only loaded when the user navigates to the Map section, ensuring fast initial page loads for the rest of the application.

## Architecture

### Frontend
- **Component**: `plugins/Map/resources/js/MapPanel.js`
- **Library**: [Leaflet.js](https://leafletjs.com/) (v1.9.4) hosted locally in `www/js/vendor/leaflet/`.
- **Loading Strategy**: CSS and JS are lazy-loaded on demand when the Map panel is initialized.

### Backend
- **Controller**: `Map\Controller\MapController`
  - `GET /@/markers`: Returns all markers for the current user.
  - `POST /@/markers`: Creates a new marker.
- **Model**: `Map\Model\MapMarker`
  - Handles database interactions using PDO prepared statements.
- **Database**: `map_markers` table.
  - Columns: `id`, `user_id`, `label`, `lat`, `lng`, `created_at`.

## Usage
1.  Navigate to the "Map" tab in the application.
2.  Click on any location on the map.
3.  Enter a label for the marker in the popup modal.
4.  Click "Save".
