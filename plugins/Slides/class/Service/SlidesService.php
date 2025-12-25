<?php

namespace Slides\Service;

use GaiaAlpha\Model\DB;

class SlidesService
{
    public function getAllDecks()
    {
        return DB::fetchAll("SELECT * FROM cms_slide_decks ORDER BY updated_at DESC");
    }

    public function getDeck($id)
    {
        return DB::fetch("SELECT * FROM cms_slide_decks WHERE id = ?", [$id]);
    }

    public function createDeck($title, $author_id = null)
    {
        DB::execute(
            "INSERT INTO cms_slide_decks (title, author_id) VALUES (?, ?)",
            [$title, $author_id]
        );
        return DB::lastInsertId();
    }

    public function updateDeck($id, $title)
    {
        return DB::execute(
            "UPDATE cms_slide_decks SET title = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$title, $id]
        );
    }

    public function deleteDeck($id)
    {
        return DB::execute("DELETE FROM cms_slide_decks WHERE id = ?", [$id]);
    }

    public function getPages($deck_id)
    {
        return DB::fetchAll(
            "SELECT * FROM cms_slide_pages WHERE deck_id = ? ORDER BY order_index ASC",
            [$deck_id]
        );
    }

    public function addPage($deck_id, $content, $slide_type = 'drawing', $order_index = null, $background_color = '#ffffff')
    {
        if ($order_index === null) {
            $maxOrder = DB::fetchColumn(
                "SELECT MAX(order_index) FROM cms_slide_pages WHERE deck_id = ?",
                [$deck_id]
            );
            $order_index = ($maxOrder !== null) ? $maxOrder + 1 : 0;
        }

        DB::execute(
            "INSERT INTO cms_slide_pages (deck_id, content, slide_type, order_index, background_color) VALUES (?, ?, ?, ?, ?)",
            [$deck_id, $content, $slide_type, $order_index, $background_color]
        );
        return DB::lastInsertId();
    }

    public function updatePage($id, $content, $slide_type = null, $background_color = null)
    {
        if ($slide_type) {
            return DB::execute(
                "UPDATE cms_slide_pages SET content = ?, slide_type = ?, background_color = COALESCE(?, background_color), updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$content, $slide_type, $background_color, $id]
            );
        }
        return DB::execute(
            "UPDATE cms_slide_pages SET content = ?, background_color = COALESCE(?, background_color), updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$content, $background_color, $id]
        );
    }

    public function deletePage($id)
    {
        return DB::execute("DELETE FROM cms_slide_pages WHERE id = ?", [$id]);
    }

    public function reorderPages($deck_id, $page_ids)
    {
        foreach ($page_ids as $index => $id) {
            DB::execute(
                "UPDATE cms_slide_pages SET order_index = ? WHERE id = ? AND deck_id = ?",
                [$index, $id, $deck_id]
            );
        }
        return true;
    }
}
