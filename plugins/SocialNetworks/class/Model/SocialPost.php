<?php

namespace SocialNetworks\Model;

use GaiaAlpha\Model\DB;

class SocialPost
{
    public static function findAll()
    {
        return DB::fetchAll("SELECT p.*, a.platform, a.account_name FROM cms_social_posts p LEFT JOIN cms_social_accounts a ON p.account_id = a.id ORDER BY p.created_at DESC");
    }

    public static function create($data)
    {
        return DB::execute(
            "INSERT INTO cms_social_posts (account_id, content, media_urls, status, platform_post_id, error_message, published_at) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $data['account_id'],
                $data['content'],
                $data['media_urls'] ?? null,
                $data['status'] ?? 'pending',
                $data['platform_post_id'] ?? null,
                $data['error_message'] ?? null,
                $data['published_at'] ?? null
            ]
        );
    }
}
