# MlPca Plugin

The **MlPca** plugin provides Principal Component Analysis (PCA) capabilities directly within the Gaia Alpha admin dashboard. It allows users to perform dimensionality reduction on datasets without external Python dependencies.

## Objective
To enable data analysis and visualization by reducing high-dimensional data (e.g., CSV) into 2-3 principal components, identifying the most variance in the dataset.

## Features
*   **Pure PHP Implementation**: Uses a native Jacobi Eigenvalue Algorithm, requiring no external libraries or extensions.
*   **CSV Import**: Paste CSV data directly into the UI.
*   **Visualization**:
    *   **Scree Plot**: Visualizes the explained variance of each component.
    *   **2D Scatter Plot**: Visualizes the alignment of data along the first two principal components.
*   **Interactive UI**: Vue.js-based frontend for real-time analysis.

## Configuration
No special configuration is required. The plugin works out of the box.
*   **Menu Item**: "PCA Analysis" under the "Tools" group.
*   **Permissions**: Restricted to Admins (`adminOnly: true` in `plugin.json`).

## Architecture

### Backend
*   **Service**: `MlPca\Service\PcaService`
    *   Implements Z-Score Standardization.
    *   Implements Covariance Matrix calculation.
    *   Implements Jacobi Eigenvalue Algorithm for diagonalization.
*   **Controller**: `MlPca\Controller\PcaController`
    *   `POST /@/ml-pca/analyze`: Accepts JSON payload `{ data: [[],...], components: 2 }`.

### Frontend
*   **Component**: `plugins/MlPca/resources/js/MlPca.js`
    *   Registers globally as `window.MlPca`.
    *   Loaded dynamically by the Admin Panel router when the valid view is active.

## Performance
*   **Complexity**: The core algorithm has a time complexity of approximately $O(N \cdot M^2)$ for covariance and $O(M^3)$ for eigendecomposition (where $N$ is rows, $M$ is features).
*   **Limits**:
    *   Ideally suited for datasets with $M < 100$ features and $N < 5000$ rows.
    *   Larger datasets may hit PHP memory or execution time limits.
    *   For huge datasets, consider using a dedicated Python microservice or extension (e.g., Rubix ML).

## Hooks
*   `framework_load_controllers_after`: Registers the `PcaController`.
*   `auth_session_data`: (Placeholder) Can be used to inject dynamic menu items if needed.
