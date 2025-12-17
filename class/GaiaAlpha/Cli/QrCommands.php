<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\System;
use GaiaAlpha\Cli\Input;
use GaiaAlpha\Cli\Output;

class QrCommands
{
    public static function handleGenerate(): void
    {
        if (Input::count() < 2) {
            Output::writeln("Usage: php cli.php qr:generate <text> <output_file> [size] [margin]");
            Output::writeln("  text: Text to encode");
            Output::writeln("  output_file: Path to save the PNG image");
            Output::writeln("  size: Module size in pixels (default: 3)");
            Output::writeln("  margin: Margin width in modules (default: 4)");
            exit(1);
        }

        $text = Input::get(0);
        $outputFile = Input::get(1);
        $size = (int) Input::get(2, 3);
        $margin = (int) Input::get(3, 4);

        // Check if qrencode is available
        if (!System::isAvailable('qrencode')) {
            Output::error("'qrencode' tool is not found in the system PATH. Please install it.");
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
            Output::error("Failed to generate QR code.");
            if (!empty($output)) {
                Output::writeln(implode("\n", $output));
            }
            exit(1);
        }

        Output::success("QR code generated successfully at: $outputFile");
    }
}
