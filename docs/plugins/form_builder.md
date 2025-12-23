# Form Builder Plugin

**Version:** 2.0.0  
**Status:** Active  
**Type:** Core Upgrade

The Form Builder plugin replaces the legacy core form functionality with a robust, feature-rich system for creating Forms, Quizzes, and Polls. It includes a built-in analytics dashboard and advanced submission management.

## Features

### 1. Multi-Type Support
-   **Standard Forms**: Collect contact info, feedback, etc.
-   **Quizzes**: Graded forms with points and correct answers. Autoscoring included.
-   **Polls**: Simple surveys to gather public opinion.

### 2. Advanced Editor
-   Drag-and-drop interface.
-   **Quiz Settings**: Assign points to fields, define correct answers (text match, option match).
-   **Poll Settings**: Toggle public results visibility (roadmap).

### 3. Analytics Dashboard
-   **Overview**: Total submissions, Average Score (for Quizzes).
-   **Visual Charts**: Bar charts showing response distribution for Select, Radio, and Checkbox fields.

### 4. Developer API

#### Routes
-   `GET /@/forms` - List forms
-   `POST /@/forms` - Create form
-   `GET /@/forms/{id}` - Get form details
-   `PUT /@/forms/{id}` - Update form
-   `DELETE /@/forms/{id}` - Delete form
-   `GET /@/forms/{id}/submissions` - Get submissions
-   `GET /@/forms/{id}/stats` - Get aggregated stats

#### Public API
-   `GET /@/public/form/{slug}` - Get public schema (hides correct answers for security).
-   `POST /@/public/form/{slug}` - Submit entry. Returns score if it is a quiz.

## Data Structure

### `forms` Table
-   `type`: 'form', 'quiz', 'poll'
-   `schema`: JSON array of fields.
-   `settings`: JSON object for extra config.

### `form_submissions` Table
-   `score`: Integer (points) for quizzes.
-   `metadata`: JSON object containing detailed results (correct/incorrect per question).

## security
-   Quiz answers are validated on the server.
-   Correct answers are stripped from the public schema endpoint to prevent cheating.
