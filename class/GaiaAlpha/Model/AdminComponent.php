<?php

namespace GaiaAlpha\Model;

class AdminComponent
{
    public static function findAll()
    {
        return DB::fetchAll("SELECT * FROM admin_components ORDER BY title ASC");
    }

    public static function findById($id)
    {
        return DB::fetch("SELECT * FROM admin_components WHERE id = ?", [$id]);
    }

    public static function create($data)
    {
        $fields = ['name', 'title', 'description', 'category', 'icon', 'view_name', 'definition', 'generated_code', 'createdBy'];
        $dbFields = ['name', 'title', 'description', 'category', 'icon', 'view_name', 'definition', 'generated_code', 'created_by'];
        $values = [];
        $placeholders = [];

        foreach ($fields as $i => $field) {
            $val = $data[$field] ?? null;
            if ($field === 'definition' && is_array($val)) {
                $val = json_encode($val);
            }
            if ($field === 'generated_code' && $val === null) {
                // Should be generated
            }
            $values[] = $val;
            $placeholders[] = '?';
        }

        // Basic implementation for now, handled more dynamically below

        $sql = "INSERT INTO admin_components (name, title, description, category, icon, view_name, definition, generated_code, created_by, version, enabled, admin_only) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, 1)";

        $params = [
            $data['name'],
            $data['title'],
            $data['description'] ?? '',
            $data['category'] ?? 'custom',
            $data['icon'] ?? 'puzzle',
            $data['view_name'] ?? $data['name'],
            is_array($data['definition']) ? json_encode($data['definition']) : $data['definition'],
            $data['generated_code'] ?? null,
            $data['created_by'] ?? null
        ];

        DB::query($sql, $params);
        return DB::lastInsertId();
    }

    public static function update($id, $data)
    {
        $fields = [];
        $params = [];

        $allowed = ['title', 'description', 'category', 'icon', 'definition', 'generated_code', 'enabled', 'admin_only'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = ?";
                $val = $data[$field];
                if ($field === 'definition' && is_array($val)) {
                    $val = json_encode($val);
                }
                $params[] = $val;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "updated_at = CURRENT_TIMESTAMP";
        $fields[] = "version = version + 1";

        $sql = "UPDATE admin_components SET " . implode(', ', $fields) . " WHERE id = ?";
        $params[] = $id;

        return DB::execute($sql, $params) > 0;
    }

    public static function delete($id)
    {
        return DB::execute("DELETE FROM admin_components WHERE id = ?", [$id]) > 0;
    }
}
