# LMS Plugin

The **LMS (Learning Management System)** plugin transforms Gaia Alpha into a powerful platform for delivering online education. It enables the creation of structured courses, modules, and lessons, and manages student enrollments.

## Features

- **Hierarchical Content**: Structure content into Courses > Modules > Lessons.
- **Support for Media**: Embed video, text, and rich content in lessons.
- **Enrollment Management**: Track student access and progress.
- **E-commerce Integration**: Automatically enroll students upon purchasing a course via the Ecommerce plugin.
- **REST API**: Full programmatic access to course data.

## Installation

The plugin is located in `plugins/Lms`. Ensure it is enabled in your configuration.

Upon activation, it will create the following database tables:
- `lms_courses`
- `lms_modules`
- `lms_lessons`
- `lms_enrollments`
- `lms_progress`

## Configuration

The plugin works out-of-the-box but can be integrated with the **Ecommerce** plugin for selling courses.

### Selling Courses
To sell a course:
1. Create the Course in the LMS.
2. Create a Product in the Ecommerce plugin with `type: 'course'` and `external_id: <course_id>`.
3. When a user purchases this product, they will be automatically enrolled.

## API Reference

### Get All Courses
**GET** `/api/lms/courses`

Returns a list of all available courses.

```json
[
  {
    "id": 1,
    "title": "Introduction to Gaia Alpha",
    "slug": "intro-gaia-alpha",
    "price": 29.99,
    "instructor_id": 1
  }
]
```

### Get Course Details
**GET** `/api/lms/courses/{id}`

Returns full course details including modules and lessons.

```json
{
  "id": 1,
  "title": "Introduction to Gaia Alpha",
  "modules": [
    {
      "id": 101,
      "title": "Getting Started",
      "lessons": [
        { "id": 501, "title": "Installation" },
        { "id": 502, "title": "Configuration" }
      ]
    }
  ]
}
```

### Create Course
**POST** `/api/lms/courses`

payload:
```json
{
  "title": "Deep Dive PHP",
  "slug": "deep-dive-php"
}
```

## Frontend Components

The plugin includes several Vue.js components in `plugins/Lms/resources/js/`:

- **LmsDashboard.js**: An admin-like interface for managing courses.
- **CoursePlayer.js**: A student-facing interface for consuming course content, featuring a sidebar navigation and content area.
