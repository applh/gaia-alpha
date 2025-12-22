<?php

namespace GaiaAlpha;

class Video
{
    /**
     * Get video metadata using ffprobe
     */
    public static function getInfo(string $path): array
    {
        if (!System::isAvailable('ffprobe')) {
            throw new \Exception("'ffprobe' tool is not found in the system PATH.");
        }

        $command = sprintf(
            'ffprobe -v quiet -print_format json -show_format -show_streams %s',
            System::escapeArg($path)
        );

        $outLines = [];
        $returnVar = 0;
        System::exec($command, $outLines, $returnVar);

        if ($returnVar !== 0) {
            throw new \Exception("Failed to retrieve media info.");
        }

        return json_decode(implode("\n", $outLines), true);
    }

    /**
     * Extract a single frame from video at specified time
     */
    public static function extractFrame(string $videoPath, string $outputPath, string $time = '00:00:01'): bool
    {
        self::ensureFfmpeg();

        $command = sprintf(
            'ffmpeg -y -ss %s -i %s -vframes 1 -q:v 2 %s',
            System::escapeArg($time),
            System::escapeArg($videoPath),
            System::escapeArg($outputPath)
        );

        $outLines = [];
        $returnVar = 0;
        System::exec($command, $outLines, $returnVar);
        return $returnVar === 0 && File::exists($outputPath);
    }

    /**
     * Trim video segment
     */
    public static function trim(string $videoPath, string $outputPath, string $start, string $duration): bool
    {
        self::ensureFfmpeg();

        $command = sprintf(
            'ffmpeg -y -ss %s -i %s -t %s -c copy %s',
            System::escapeArg($start),
            System::escapeArg($videoPath),
            System::escapeArg($duration),
            System::escapeArg($outputPath)
        );

        $outLines = [];
        $returnVar = 0;
        System::exec($command, $outLines, $returnVar);
        return $returnVar === 0 && File::exists($outputPath);
    }

    /**
     * Compress video using CRF
     */
    public static function compress(string $videoPath, string $outputPath, int $crf = 28): bool
    {
        self::ensureFfmpeg();

        $command = sprintf(
            'ffmpeg -y -i %s -vcodec libx264 -crf %d %s',
            System::escapeArg($videoPath),
            $crf,
            System::escapeArg($outputPath)
        );

        $outLines = [];
        $returnVar = 0;
        System::exec($command, $outLines, $returnVar);
        return $returnVar === 0 && File::exists($outputPath);
    }

    /**
     * Ensure ffmpeg is available
     */
    private static function ensureFfmpeg(): void
    {
        if (!System::isAvailable('ffmpeg')) {
            throw new \Exception("'ffmpeg' tool is not found in the system PATH.");
        }
    }
}
