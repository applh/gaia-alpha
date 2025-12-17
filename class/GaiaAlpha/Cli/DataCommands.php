<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Seeder;

class DataCommands
{
    public static function handleSeed(): void
    {
        echo "🌱 Seeding database...\n";

        // Initialize database connection
        \GaiaAlpha\Model\DB::connect();

        // Get first admin user
        $user = \GaiaAlpha\Model\DB::fetch("SELECT * FROM users WHERE level = 10 LIMIT 1");

        if (!$user) {
            echo "❌ Error: No admin user found. Please create an admin user first.\n";
            exit(1);
        }

        $userId = $user['id'];

        try {
            Seeder::run($userId);
            echo "✅ Database seeded successfully!\n";
        } catch (\Exception $e) {
            echo "❌ Error seeding database: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
}
