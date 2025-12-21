# Apache Spark Plugin

The **Apache Spark** plugin provides integration with Apache Spark for large-scale data processing and analytics. It enables Gaia Alpha users and AI agents to submit jobs, monitor statuses, and run distributed SQL queries.

## Objective
To extend the system's data processing capabilities by leveraging Apache Spark's distributed computing engine, allowing for handling datasets that exceed local memory/processing limits.

## Configuration
The plugin requires a connection to a Spark cluster, typically via **Apache Livy** (for job submission) or the **Spark Thrift Server** (for SQL queries).

Add the following to your environment:
- `SPARK_LIVY_URL`: The endpoint for the Livy REST API (e.g., `http://spark-master:8998`).
- `SPARK_THRIFT_DSN`: The PDO DSN for the Spark Thrift Server (e.g., `odbc:DSN=SparkThrift`).
- `SPARK_DEFAULT_CONFIG`: Optional JSON string for default Spark configurations.

## Features

### 1. Job Submission (Livy)
Submit Spark jobs (JAR or Python) asynchronously through the system.
- **REST API**: `POST /@/spark/jobs/submit`
- **MCP Tool**: `submit_spark_job`

### 2. Live Monitoring
A dedicated dashboard to monitor the status of running and completed Spark applications.
- **View**: `/admin/spark/dashboard`

### 3. Distributed SQL
Execute Spark SQL queries against your data lake or data warehouse.
- **REST API**: `POST /@/spark/sql/query`
- **MCP Tool**: `execute_spark_sql`

## Architecture

### Backend
- **Service**: `ApacheSpark\Service\SparkService`
    - Encapsulates Livy client logic.
    - Manages JDBC/ODBC connections to Thrift Server.
- **Controller**: `ApacheSpark\Controller\SparkController`
    - Exposes endpoints for the UI and external API.
- **Tooling**:
    - `ApacheSpark\Tool\SubmitSparkJob`: MCP wrapper for job submission.
    - `ApacheSpark\Tool\GetSparkJobStatus`: MCP wrapper for job monitoring.

### Frontend
- **Component**: `plugins/ApacheSpark/resources/js/SparkDashboard.js`
    - Visualizes job history and logs.
    - Provides a Query Runner for Spark SQL.

## Example Usage (PHP)

```php
use ApacheSpark\Service\SparkService;

$spark = new SparkService();
$jobId = $spark->submitJob([
    'file' => 's3a://bucket/jobs/analytics.py',
    'args' => ['--date', '2025-12-21']
]);

echo "Job submitted with ID: $jobId";
```

## Hooks
- `framework_load_controllers_after`: Registers the `SparkController`.
- `auth_session_data`: Injects "Spark Dashboard" into the Tools menu for authorized users.

## Step-by-Step Implementation Guide

To add this plugin to the system, follow these steps:

1.  **Create Plugin Structure**:
    ```bash
    mkdir -p plugins/ApacheSpark/class/{Service,Controller,Tool}
    mkdir -p plugins/ApacheSpark/resources/js
    ```

2.  **Define Plugin Metadata**: Create `plugins/ApacheSpark/plugin.json` with name, version, and `adminOnly: true`.

3.  **Implement `SparkService`**:
    - Focus on HTTP client logic for Livy.
    - Use `PDO` if connecting via Thrift Server (JDBC/ODBC).

4.  **Create `SparkController`**:
    - Wrap service methods into REST endpoints.
    - Ensure `Response::json()` is used for consistency.

5.  **Develop MCP Tools**:
    - Create classes in `plugins/ApacheSpark/class/Tool/`.
    - These will be auto-discovered by the `McpServer` plugin.

6.  **Register in `index.php`**:
    - Use `Hook::add('framework_load_controllers_after', ...)` to initialize the controller.
    - Use `\GaiaAlpha\UiManager::registerComponent(...)` for the frontend.

7.  **Build the Dashboard**:
    - Create `SparkDashboard.js` using the standard Vue-like component pattern used in other plugins.

## Security
- Job submission and SQL execution require **Admin Level 100**.
- Connection strings and credentials should be managed via `Env` or the encrypted `DataStore`.
