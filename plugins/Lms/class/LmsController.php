<?php

namespace Lms;

use GaiaAlpha\Request;
use GaiaAlpha\Response;
use Lms\Model\Course;
use Lms\Model\Module;
use Lms\Model\Lesson;

class LmsController
{

    public function getCourses()
    {
        $courses = Course::all();
        Response::json($courses);
    }

    public function getCourse($id)
    {
        $course = Course::find($id);
        if (!$course) {
            Response::json(['error' => 'Course not found'], 404);
            return;
        }

        $modules = Course::getModules($id);
        foreach ($modules as &$module) {
            $module['lessons'] = Module::getLessons($module['id']);
        }

        $course['modules'] = $modules;
        Response::json($course);
    }

    public function createCourse()
    {
        $data = Request::input();
        // Validation logic here
        $id = Course::create($data);
        Response::json(['id' => $id, 'message' => 'Course created'], 201);
    }

    public function registerRoutes()
    {
        \GaiaAlpha\Router::get('/api/lms/courses', [$this, 'getCourses']);
        \GaiaAlpha\Router::get('/api/lms/courses/(\d+)', [$this, 'getCourse']);
        \GaiaAlpha\Router::post('/api/lms/courses', [$this, 'createCourse']);
    }
}
