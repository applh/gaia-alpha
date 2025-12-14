<?php
namespace GaiaAlpha\Model;

class Menu
{
    public static function all()
    {
        return DB::fetchAll("SELECT * FROM menus ORDER BY id DESC");
    }

    public static function find(int $id)
    {
        return DB::fetch("SELECT * FROM menus WHERE id = ?", [$id]);
    }

    public static function findByLocation(string $location)
    {
        return DB::fetch("SELECT * FROM menus WHERE location = ? LIMIT 1", [$location]);
    }

    public static function create(array $data)
    {
        $fields = ['title', 'location', 'items'];
        $columns = [];
        $values = [];
        $placeholders = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $columns[] = $field;
                $values[] = $data[$field];
                $placeholders[] = '?';
            }
        }

        $columns[] = 'created_at';
        $columns[] = 'updated_at';
        $values[] = date('Y-m-d H:i:s');
        $values[] = date('Y-m-d H:i:s');
        $placeholders[] = '?';
        $placeholders[] = '?';

        $sql = "INSERT INTO menus (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        DB::execute($sql, $values);
        return DB::lastInsertId();
    }

    public static function update(int $id, array $data)
    {
        $fields = ['title', 'location', 'items'];
        $sets = [];
        $values = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $sets[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($sets)) {
            return false;
        }

        $sets[] = "updated_at = ?";
        $values[] = date('Y-m-d H:i:s');
        $values[] = $id;

        $sql = "UPDATE menus SET " . implode(', ', $sets) . " WHERE id = ?";
        return DB::execute($sql, $values) > 0;
    }

    public static function delete(int $id)
    {
        return DB::execute("DELETE FROM menus WHERE id = ?", [$id]) > 0;
    }
}
