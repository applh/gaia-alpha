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

    public function saveProgress()
    {
        // Stub
        Response::json(['success' => true]);
    }

    public function registerRoutes()
    {
        \GaiaAlpha\Router::add('GET', '/@/api/lms/courses', [$this, 'getCourses']);
        \GaiaAlpha\Router::add('GET', '/@/api/lms/courses/([0-9]+)', [$this, 'getCourse']);
        \GaiaAlpha\Router::add('POST', '/@/api/lms/courses', [$this, 'createCourse']);
        \GaiaAlpha\Router::add('POST', '/@/api/lms/progress', [$this, 'saveProgress']);
    }
}
