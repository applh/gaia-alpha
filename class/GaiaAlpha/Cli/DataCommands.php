<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Seeder;
use GaiaAlpha\Controller\DbController;

class DataCommands
{
    public static function handleSeed(): void
    {
        echo "🌱 Seeding database...\n";

        // Initialize database connection
        DbController::connect();

        // Get first admin user
        $pdo = DbController::getPdo();
        $stmt = $pdo->query("SELECT id FROM users WHERE level >= 100 LIMIT 1");
        $user = $stmt->fetch();

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
