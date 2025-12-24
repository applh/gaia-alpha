<?php

namespace Lms\Model;

use GaiaAlpha\Model\DB;

class Module
{
    public static function create($data)
    {
        DB::query("INSERT INTO lms_modules (course_id, title, sort_order) VALUES (?, ?, ?)", [
            $data['course_id'],
            $data['title'],
            $data['sort_order'] ?? 0
        ]);
        return DB::lastInsertId();
    }

    public static function getLessons($moduleId)
    {
        return DB::fetchAll("SELECT * FROM lms_lessons WHERE module_id = ? ORDER BY sort_order ASC", [$moduleId]);
    }
}
