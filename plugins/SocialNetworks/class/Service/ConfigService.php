<?php

namespace SocialNetworks\Service;

use GaiaAlpha\Env;

class ConfigService
{
    /**
     * Get social platform configuration.
     * Prioritizes Env variables, then plugin.json defaults.
     */
    public function getPlatformConfig($platform)
    {
        $configs = [
            'x' => [
                'api_key' => Env::get('X_API_KEY'),
                'client_id' => Env::get('X_CLIENT_ID'),
            ],
            'linkedin' => [
                'client_id' => Env::get('LINKEDIN_CLIENT_ID'),
                'client_secret' => Env::get('LINKEDIN_CLIENT_SECRET'),
            ],
            'youtube' => [
                'client_id' => Env::get('YOUTUBE_CLIENT_ID'),
                'client_secret' => Env::get('YOUTUBE_CLIENT_SECRET'),
            ],
            'tiktok' => [
                'client_id' => Env::get('TIKTOK_CLIENT_ID'),
                'client_secret' => Env::get('TIKTOK_CLIENT_SECRET'),
            ]
        ];

        return $configs[strtolower($platform)] ?? [];
    }

    public function saveTokens($platform, $accessToken, $refreshToken = null, $expiresIn = null)
    {
        // Logic to store tokens securely in DB or encrypted file
    }
}
