<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Seeder;
use GaiaAlpha\Cli\Output;

class DataCommands
{
    public static function handleSeed(): void
    {
        Output::info("🌱 Seeding database...");

        // Get first admin user
        $user = \GaiaAlpha\Model\DB::fetch("SELECT * FROM users WHERE level = 100 LIMIT 1");

        if (!$user) {
            Output::error("No admin user found. Please create an admin user first.");
            exit(1);
        }

        $userId = $user['id'];

        try {
            Seeder::run($userId);
            Output::success("Database seeded successfully!");
        } catch (\Exception $e) {
            Output::error("Error seeding database: " . $e->getMessage());
            exit(1);
        }
    }
}
