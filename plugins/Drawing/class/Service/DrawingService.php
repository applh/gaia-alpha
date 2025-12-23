<?php

namespace Drawing\Service;

use GaiaAlpha\Model\DB;

class DrawingService
{
    public function getAllArtworks()
    {
        return DB::fetchAll("SELECT * FROM cms_drawings ORDER BY updated_at DESC");
    }

    public function getArtwork($id)
    {
        return DB::fetch("SELECT * FROM cms_drawings WHERE id = ?", [$id]);
    }

    public function createArtwork($title, $description, $content, $level, $background_image)
    {
        DB::execute(
            "INSERT INTO cms_drawings (title, description, content, level, background_image) VALUES (?, ?, ?, ?, ?)",
            [$title, $description, $content, $level, $background_image]
        );
        return DB::lastInsertId();
    }

    public function updateArtwork($id, $title, $description, $content, $level, $background_image)
    {
        return DB::execute(
            "UPDATE cms_drawings SET title = ?, description = ?, content = ?, level = ?, background_image = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$title, $description, $content, $level, $background_image, $id]
        );
    }

    public function deleteArtwork($id)
    {
        return DB::execute("DELETE FROM cms_drawings WHERE id = ?", [$id]);
    }
}
