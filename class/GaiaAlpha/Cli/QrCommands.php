<?php

namespace GaiaAlpha\Cli;

use Exception;
use GaiaAlpha\System;

class QrCommands
{
    public static function handleGenerate(): void
    {
        global $argv;

        if (count($argv) < 4) {
            echo "Usage: php cli.php qr:generate <text> <output_file> [size] [margin]\n";
            echo "  text: Text to encode\n";
            echo "  output_file: Path to save the PNG image\n";
            echo "  size: Module size in pixels (default: 3)\n";
            echo "  margin: Margin width in modules (default: 4)\n";
            exit(1);
        }

        $text = $argv[2];
        $outputFile = $argv[3];
        $size = isset($argv[4]) ? (int) $argv[4] : 3;
        $margin = isset($argv[5]) ? (int) $argv[5] : 4;

        // Check if qrencode is available
        if (!System::isAvailable('qrencode')) {
            echo "Error: 'qrencode' tool is not found in the system PATH. Please install it.\n";
            exit(1);
        }

        $command = sprintf(
            'qrencode -s %d -m %d -o %s %s',
            $size,
            $margin,
            System::escapeArg($outputFile),
            System::escapeArg($text)
        );

        $output = [];
        $returnVar = 0;
        System::exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            echo "Error: Failed to generate QR code.\n";
            if (!empty($output)) {
                echo implode("\n", $output) . "\n";
            }
            exit(1);
        }

        echo "QR code generated successfully at: $outputFile\n";
    }
}
