# 3D Earth Map Library Comparison

This document provides a comparison of popular JavaScript libraries for rendering 3D globes and maps, which guided our decision to use **Globe.gl**.

## 1. Globe.gl

**Website**: [globe.gl](https://globe.gl)
**Based on**: Three.js

**Pros**:
- **Easy to Use**: High-level API specifically for globes.
- **Performance**: Efficiently handles large datasets (thousands of points/arcs) using Three.js instancing.
- **Visuals**: Beautiful default aesthetic (atmosphere, stars, etc.).
- **Features**: Built-in support for arcs, paths, labels, honeycombs, and heatmaps.
- **Lightweight**: Smaller footprint compared to full GIS suites.

**Cons**:
- **Limited Projection**: Strictly spherical (no flat map projection transition).
- **Less GIS**: Not a full GIS engine; less support for complex geospatial data formats (like shapefiles) out of the box compared to Cesium.

**Use Case**: Dashboard visualizations, data art, simple store locators on a globe.

## 2. CesiumJS

**Website**: [cesium.com](https://cesium.com/platform/cesiumjs/)
**Based on**: WebGL (custom engine)

**Pros**:
- **Professional GIS**: Industry standard for high-precision geospatial data.
- **Accuracy**: Extremely accurate terrain, coordinate systems, and time-dynamic data.
- **3D Tiles**: Supports streaming massive 3D datasets (cities, photogrammetry).
- **Views**: Supports 3D Globe, 2D Map, and Columbus View (2.5D) transitions.

**Cons**:
- **Complexity**: Steep learning curve.
- **Size**: Large bundle size.
- **Overkill**: Often too heavy for simple marker visualization websites.

**Use Case**: Flight simulators, urban planning, defense, complex geospatial analysis.

## 3. Mapbox GL JS (FreeCamera / Globe View)

**Website**: [mapbox.com](https://mapbox.com)
**Based on**: WebGL

**Pros**:
- **Hybrid**: Excellent 2D map that can zoom out to a 3D globe.
- **Vector Tiles**: Best-in-class vector tile rendering.
- **Design**: Studio tool allows for incredible custom map styles.

**Cons**:
- **Licensing**: Proprietary/Commercial (Maplibre GL JS is a free fork but globe support varies).
- **Cost**: Usage-based pricing can get expensive.
- **3D Limitations**: Globe view is more visual than functional compared to Globe.gl's dedicated data layers.

**Use Case**: Consumer apps like weather or travel that need smooth zoom from street level to globe.

## 4. Google Maps Platform (Photorealistic 3D Tiles)

**Website**: [developers.google.com/maps](https://developers.google.com/maps)

**Pros**:
- **Data**: Unmatched real-world photorealistic 3D data.
- **Familiarity**: Users know the interface.

**Cons**:
- **Cost**: Expensive API.
- **Control**: Less control over the rendering pipeline compared to Three.js solutions.

---

## Conclusion & Decision

We chose **Globe.gl** for Gaia Alpha because:
1.  **Simplicity**: It effectively visualizes our marker data without complex GIS setup.
2.  **Aesthetics**: It provides a visually striking "command center" feel out of the box.
3.  **Integration**: Easy to lazy-load and integrate as a standalone Vue component alongside Leaflet.
