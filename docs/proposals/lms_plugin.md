# LMS (Learning Management System) Plugin Proposal

## 1. Overview
The **LMS Plugin** will provide a comprehensive system for creating, managing, and delivering online courses within the Gaia Alpha framework. It allows users (instructors) to structure content into courses, modules, and lessons, and tracks student progress.

It is designed to integrate seamlessly with the **E-commerce Plugin** to enable the sale of courses.

## 2. Goals
- **Course Management**: Structured curriculum (Course -> Modules -> Lessons).
- **Content Delivery**: Support for video, text, quizzes (via FormBuilder integration), and attachments.
- **Progress Tracking**: Track user completion of lessons and courses.
- **Student Management**: Enrolled users and their status.
- **Integration**: Work with E-commerce for paid access.

## 3. Database Schema
The plugin will define its tables in `plugins/Lms/schema.sql`.

```sql
-- Courses Table
CREATE TABLE IF NOT EXISTS lms_courses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    thumbnail VARCHAR(255),
    instructor_id INTEGER,
    status VARCHAR(20) DEFAULT 'draft', -- draft, published, archived
    price DECIMAL(10, 2) DEFAULT 0.00,  -- Basic price (can be overridden by Ecommerce)
    is_free BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Modules (Sections)
CREATE TABLE IF NOT EXISTS lms_modules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    course_id INTEGER NOT NULL,
    title VARCHAR(255) NOT NULL,
    sort_order INTEGER DEFAULT 0,
    FOREIGN KEY(course_id) REFERENCES lms_courses(id) ON DELETE CASCADE
);

-- Lessons (Units)
CREATE TABLE IF NOT EXISTS lms_lessons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    module_id INTEGER NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    type VARCHAR(20) DEFAULT 'text', -- text, video, quiz
    content TEXT,                    -- HTML content or Video URL
    video_id VARCHAR(255),           -- Integration with MediaLibrary or external provider
    duration INTEGER,                -- in seconds
    is_preview BOOLEAN DEFAULT 0,    -- Free preview available?
    sort_order INTEGER DEFAULT 0,
    FOREIGN KEY(module_id) REFERENCES lms_modules(id) ON DELETE CASCADE
);

-- Enrollments
CREATE TABLE IF NOT EXISTS lms_enrollments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    course_id INTEGER NOT NULL,
    status VARCHAR(20) DEFAULT 'active', -- active, completed, expired
    enrolled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL,
    FOREIGN KEY(course_id) REFERENCES lms_courses(id) ON DELETE CASCADE
);

-- Progress
CREATE TABLE IF NOT EXISTS lms_progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    lesson_id INTEGER NOT NULL,
    course_id INTEGER NOT NULL,      -- Denormalized for easier query
    completed BOOLEAN DEFAULT 0,
    completed_at DATETIME NULL,
    last_viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(lesson_id) REFERENCES lms_lessons(id) ON DELETE CASCADE
);
```

## 4. Architecture

### 4.1 Namespace & Structure
Namespace: `Lms`
Path: `plugins/Lms/`

- `plugin.json`: Metadata.
- `index.php`: Entry point, hooks listener.
- `class/Course.php`: Model.
- `class/EnrollmentService.php`: Logic for enrolling/unenrolling.
- `class/LmsController.php`: API endpoints.
- `resources/js/`: Frontend components (CourseEditor, CoursePlayer).

### 4.2 Integration with E-commerce
The LMS plugin will listen for events from the E-commerce system (or vice-versa).

**Strategy**: "Product Type Provider"
1.  **LMS** registers a "product type" called `course` with the **E-commerce** plugin.
2.  When creating a product in E-commerce, the admin selects "Course" type and links it to an `lms_courses` ID.
3.  **E-commerce** fires an event `order_paid` containing the product details.
4.  **LMS** listens for `order_paid`. If the product is a course, it calls `EnrollmentService::enroll($user_id, $course_id)`.

```php
// In plugins/Lms/index.php
Hook::add('ecommerce_order_paid', function($order, $items) {
    foreach ($items as $item) {
        if ($item['type'] === 'course') {
            Lms\EnrollmentService::enroll($order['user_id'], $item['related_id']);
        }
    }
});
```

### 4.3 Integration with FormBuilder
- Quizzes will be created using the `FormBuilder` plugin.
- A lesson of type `quiz` will reference a `form_id`.
- LMS will listen for `form_submission` to mark the lesson as completed if the score passes.

## 5. UI Components
1.  **Instructor Dashboard**: Manage courses, modules, lessons. View students.
2.  **Course Player**: Frontend for students to take the course. Sidebar with modules, main content area, "Mark Complete" button.
3.  **My Learning**: Student dashboard showing enrolled courses and progress.

## 6. Security
- Use `Middleware` to ensure only enrolled users can access lesson content (unless `is_preview` is true).
- `manage_courses` permission for Instructors/Admins.
