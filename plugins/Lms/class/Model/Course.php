<?php

namespace Lms\Model;

use GaiaAlpha\Model\DB;

class Course
{
    public static function all()
    {
        return DB::fetchAll("SELECT * FROM lms_courses ORDER BY created_at DESC");
    }

    public static function find($id)
    {
        return DB::fetch("SELECT * FROM lms_courses WHERE id = ?", [$id]);
    }

    public static function create($data)
    {
        $sql = "INSERT INTO lms_courses (title, slug, description, instructor_id, status, price, is_free) VALUES (?, ?, ?, ?, ?, ?, ?)";
        DB::query($sql, [
            $data['title'],
            $data['slug'],
            $data['description'] ?? '',
            $data['instructor_id'] ?? 1, // Default to admin for now
            $data['status'] ?? 'draft',
            $data['price'] ?? 0.00,
            $data['is_free'] ?? 0
        ]);
        return DB::lastInsertId();
    }

    public static function update($id, $data)
    {
        // Dynamic update is better, but keeping it simple for now
        $sql = "UPDATE lms_courses SET title = ?, description = ?, status = ?, price = ?, is_free = ? WHERE id = ?";
        DB::query($sql, [
            $data['title'],
            $data['description'],
            $data['status'],
            $data['price'],
            $data['is_free'],
            $id
        ]);
    }

    public static function delete($id)
    {
        DB::query("DELETE FROM lms_courses WHERE id = ?", [$id]);
    }

    public static function getModules($courseId)
    {
        return DB::fetchAll("SELECT * FROM lms_modules WHERE course_id = ? ORDER BY sort_order ASC", [$courseId]);
    }
}
