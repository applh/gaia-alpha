<?php

namespace NodeEditor\Service;

use GaiaAlpha\Model\DB;

class NodeEditorService
{

    public function getAllDiagrams()
    {
        return DB::fetchAll("SELECT * FROM cms_node_diagrams ORDER BY updated_at DESC");
    }

    public function getDiagram($id)
    {
        return DB::fetch("SELECT * FROM cms_node_diagrams WHERE id = ?", [$id]);
    }

    public function createDiagram($title, $description, $content)
    {
        DB::execute(
            "INSERT INTO cms_node_diagrams (title, description, content) VALUES (?, ?, ?)",
            [$title, $description, $content]
        );
        return DB::lastInsertId();
    }

    public function updateDiagram($id, $title, $description, $content)
    {
        return DB::execute(
            "UPDATE cms_node_diagrams SET title = ?, description = ?, content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$title, $description, $content, $id]
        );
    }

    public function deleteDiagram($id)
    {
        return DB::execute("DELETE FROM cms_node_diagrams WHERE id = ?", [$id]);
    }
}
