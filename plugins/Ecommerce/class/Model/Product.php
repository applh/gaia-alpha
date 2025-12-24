<?php

namespace Ecommerce\Model;

use GaiaAlpha\Model\DB;

class Product
{
    public static function all()
    {
        return DB::fetchAll("SELECT * FROM ecommerce_products ORDER BY created_at DESC");
    }

    public static function find($id)
    {
        return DB::fetch("SELECT * FROM ecommerce_products WHERE id = ?", [$id]);
    }

    public static function create($data)
    {
        $sql = "INSERT INTO ecommerce_products (title, slug, sku, price, type, external_id, description) VALUES (?, ?, ?, ?, ?, ?, ?)";
        DB::query($sql, [
            $data['title'],
            $data['slug'],
            $data['sku'] ?? null,
            $data['price'],
            $data['type'] ?? 'simple',
            $data['external_id'] ?? null,
            $data['description'] ?? ''
        ]);
        return DB::lastInsertId();
    }
}
