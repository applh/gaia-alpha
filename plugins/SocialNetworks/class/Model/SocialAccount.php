<?php

namespace SocialNetworks\Model;

use GaiaAlpha\Model\DB;

class SocialAccount
{
    public static function findAll()
    {
        return DB::fetchAll("SELECT * FROM cms_social_accounts ORDER BY created_at DESC");
    }

    public static function find($id)
    {
        return DB::fetch("SELECT * FROM cms_social_accounts WHERE id = ?", [$id]);
    }

    public static function create($data)
    {
        return DB::execute(
            "INSERT INTO cms_social_accounts (platform, account_name, account_id, access_token, refresh_token, expires_at, settings) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $data['platform'],
                $data['account_name'] ?? null,
                $data['account_id'] ?? null,
                $data['access_token'] ?? null,
                $data['refresh_token'] ?? null,
                $data['expires_at'] ?? null,
                $data['settings'] ?? null
            ]
        );
    }

    public static function delete($id)
    {
        return DB::execute("DELETE FROM cms_social_accounts WHERE id = ?", [$id]) > 0;
    }
}
