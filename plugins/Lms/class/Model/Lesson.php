<?php

namespace Lms\Model;

use GaiaAlpha\Model\DB;

class Lesson
{
    public static function find($id)
    {
        return DB::fetch("SELECT * FROM lms_lessons WHERE id = ?", [$id]);
    }

    public static function create($data)
    {
        DB::query("INSERT INTO lms_lessons (module_id, title, slug, type, content, video_id, duration, is_preview, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", [
            $data['module_id'],
            $data['title'],
            $data['slug'],
            $data['type'] ?? 'text',
            $data['content'] ?? '',
            $data['video_id'] ?? null,
            $data['duration'] ?? 0,
            $data['is_preview'] ?? 0,
            $data['sort_order'] ?? 0
        ]);
        return DB::lastInsertId();
    }
}
