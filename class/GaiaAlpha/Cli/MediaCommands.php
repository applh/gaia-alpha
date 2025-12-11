<?php

namespace GaiaAlpha\Cli;

use GaiaAlpha\Media;
use GaiaAlpha\Env;
use GaiaAlpha\System;
class MediaCommands
{
    private static function getMedia(): Media
    {
        return new Media(Env::get('path_data'));
    }

    private static function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public static function handleStats(): void
    {
        $stats = self::getMedia()->getStats();
        echo "Media Storage Stats:\n";
        echo "--------------------\n";
        echo "Uploads: " . $stats['uploads']['count'] . " files (" . self::formatBytes($stats['uploads']['size']) . ")\n";
        echo "Cache:   " . $stats['cache']['count'] . " files (" . self::formatBytes($stats['cache']['size']) . ")\n";
    }

    public static function handleClearCache(): void
    {
        $count = self::getMedia()->clearCache();
        echo "Cache cleared. Deleted $count files.\n";
    }

    public static function handleProcess(): void
    {
        global $argv;

        if (count($argv) < 4) {
            echo "Usage: php cli.php media:process <input_file> <output_file> [width] [height] [quality] [fit] [rotate] [flip] [filter]\n";
            exit(1);
        }

        $input = $argv[2];
        $output = $argv[3];
        $width = isset($argv[4]) ? (int) $argv[4] : 0;
        $height = isset($argv[5]) ? (int) $argv[5] : 0;
        $quality = isset($argv[6]) ? (int) $argv[6] : 80;
        $fit = isset($argv[7]) ? $argv[7] : 'contain';
        $rotate = isset($argv[8]) ? (int) $argv[8] : 0;
        $flip = isset($argv[9]) ? $argv[9] : '';
        $filter = isset($argv[10]) ? $argv[10] : '';

        if (!file_exists($input)) {
            echo "Error: Input file not found: $input\n";
            exit(1);
        }

        self::getMedia()->processImage($input, $output, $width, $height, $quality, $fit, $rotate, $flip, $filter);
        echo "Image processed and saved to $output\n";
    }

    public static function handleBatchProcess(): void
    {
        global $argv;

        if (count($argv) < 4) {
            echo "Usage: php cli.php media:batch-process <input_dir> <output_dir> [w] [h] [q] [fit] [rot] [flip] [filter] [ext]\n";
            exit(1);
        }

        $inputDir = $argv[2];
        $outputDir = $argv[3];
        $width = isset($argv[4]) ? (int) $argv[4] : 0;
        $height = isset($argv[5]) ? (int) $argv[5] : 0;
        $quality = isset($argv[6]) ? (int) $argv[6] : 80;
        $fit = isset($argv[7]) ? $argv[7] : 'contain';
        $rotate = isset($argv[8]) ? (int) $argv[8] : 0;
        $flip = isset($argv[9]) ? $argv[9] : '';
        $filter = isset($argv[10]) ? $argv[10] : '';
        $ext = isset($argv[11]) ? $argv[11] : 'webp';

        if (!is_dir($inputDir)) {
            echo "Error: Input directory not found: $inputDir\n";
            exit(1);
        }

        if (!is_dir($outputDir)) {
            if (!mkdir($outputDir, 0755, true)) {
                echo "Error: Failed to create output directory: $outputDir\n";
                exit(1);
            }
        }

        $files = glob($inputDir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
        if (empty($files)) {
            echo "No images found in $inputDir\n";
            return;
        }

        $count = 0;
        foreach ($files as $file) {
            $filename = pathinfo($file, PATHINFO_FILENAME);
            $outFile = $outputDir . '/' . $filename . '.' . $ext;

            echo "Processing: " . basename($file) . " -> " . basename($outFile) . "\n";
            self::getMedia()->processImage($file, $outFile, $width, $height, $quality, $fit, $rotate, $flip, $filter);
            $count++;
        }

        echo "Batch processing complete. Processed $count images.\n";
    }

    public static function handleTransparent(): void
    {
        global $argv;

        if (count($argv) < 5) {
            echo "Usage: php cli.php media:transparent <input_file> <output_file> <hex_color> [fuzz]\n";
            echo "  hex_color: Target color to make transparent (e.g. #FFFFFF)\n";
            echo "  fuzz: Color distance tolerance (default: 0)\n";
            exit(1);
        }

        $input = $argv[2];
        $output = $argv[3];
        $hex = ltrim($argv[4], '#');
        $fuzz = isset($argv[5]) ? (int) $argv[5] : 0;

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (strlen($hex) !== 6) {
            echo "Error: Invalid hex color format.\n";
            exit(1);
        }

        $color = [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        ];

        if (!file_exists($input)) {
            echo "Error: Input file not found: $input\n";
            exit(1);
        }

        self::getMedia()->makeTransparent($input, $output, $color, $fuzz);
        echo "Transparency applied. Saved to $output\n";
    }

    public static function handleExtractFrame(): void
    {
        global $argv;

        if (count($argv) < 4) {
            echo "Usage: php cli.php media:extract-frame <video_file> <output_image> [time]\n";
            echo "  time: Time offset (default: 00:00:01)\n";
            exit(1);
        }

        $video = $argv[2];
        $output = $argv[3];
        $time = isset($argv[4]) ? $argv[4] : '00:00:01';

        if (!System::isAvailable('ffmpeg')) {
            echo "Error: 'ffmpeg' tool is not found in the system PATH. Please install it.\n";
            exit(1);
        }

        if (!file_exists($video)) {
            echo "Error: Video file not found: $video\n";
            exit(1);
        }

        $command = sprintf(
            'ffmpeg -y -ss %s -i %s -vframes 1 -q:v 2 %s',
            System::escapeArg($time),
            System::escapeArg($video),
            System::escapeArg($output)
        );

        $outLines = [];
        $returnVar = 0;
        System::exec($command, $outLines, $returnVar);

        if ($returnVar !== 0) {
            echo "Error: Failed to extract frame.\n";
            exit(1);
        }

        if (!file_exists($output)) {
            echo "Error: Output file was not created.\n";
            exit(1);
        }

        echo "Frame extracted successfully to $output\n";
    }

    public static function handleExtractAudio(): void
    {
        global $argv;

        if (count($argv) < 4) {
            echo "Usage: php cli.php media:extract-audio <video_file> <output_audio> [start_time] [duration]\n";
            echo "  start_time: Start time (default: 00:00:00)\n";
            echo "  duration: Duration to extract (default: full)\n";
            exit(1);
        }

        $video = $argv[2];
        $output = $argv[3];
        $start = isset($argv[4]) ? $argv[4] : '00:00:00';
        $duration = isset($argv[5]) ? $argv[5] : null;

        if (!System::isAvailable('ffmpeg')) {
            echo "Error: 'ffmpeg' tool is not found in the system PATH. Please install it.\n";
            exit(1);
        }

        if (!file_exists($video)) {
            echo "Error: Video file not found: $video\n";
            exit(1);
        }

        $cmdParts = [
            'ffmpeg',
            '-y',
            '-ss',
            System::escapeArg($start),
            '-i',
            System::escapeArg($video),
        ];

        if ($duration !== null) {
            $cmdParts[] = '-t';
            $cmdParts[] = System::escapeArg($duration);
        }

        $cmdParts[] = '-vn'; // No video
        // Using copy might fail if container incompatible, let's map simply or re-encode if output implies it.
        // For simplicity/safety let's default to basic re-encode or auto selection by ffmpeg based on extension.
        // Removing '-acodec copy' allows ffmpeg to pick based on extension (e.g. .mp3 -> mp3).

        $cmdParts[] = System::escapeArg($output);

        $command = implode(' ', $cmdParts);

        $outLines = [];
        $returnVar = 0;
        System::exec($command, $outLines, $returnVar);

        if ($returnVar !== 0) {
            echo "Error: Failed to extract audio.\n";
            exit(1);
        }

        if (!file_exists($output)) {
            echo "Error: Output file was not created.\n";
            exit(1);
        }

        echo "Audio extracted successfully to $output\n";
    }

    public static function handleExtractFrames(): void
    {
        global $argv;

        if (count($argv) < 7) {
            echo "Usage: php cli.php media:extract-frames <video_file> <output_dir> <start_time> <end_time> <count>\n";
            echo "  start_time: HH:MM:SS\n";
            echo "  end_time: HH:MM:SS\n";
            exit(1);
        }

        $video = $argv[2];
        $outputDir = $argv[3];
        $start = $argv[4];
        $end = $argv[5];
        $count = (int) $argv[6];

        if (!System::isAvailable('ffmpeg')) {
            echo "Error: 'ffmpeg' tool is not found in the system PATH. Please install it.\n";
            exit(1);
        }

        if (!file_exists($video)) {
            echo "Error: Video file not found: $video\n";
            exit(1);
        }

        if (!is_dir($outputDir)) {
            if (!mkdir($outputDir, 0755, true)) {
                echo "Error: Failed to create output directory: $outputDir\n";
                exit(1);
            }
        }

        $startTime = self::timeToSeconds($start);
        $endTime = self::timeToSeconds($end);

        if ($startTime === false || $endTime === false) {
            echo "Error: Invalid time format. Use HH:MM:SS\n";
            exit(1);
        }

        if ($endTime <= $startTime) {
            echo "Error: End time must be greater than start time.\n";
            exit(1);
        }

        if ($count <= 1) {
            $interval = 0;
        } else {
            $interval = ($endTime - $startTime) / ($count - 1);
        }

        echo "Extracting $count frames from $start to $end (Interval: " . number_format($interval, 2) . "s)...\n";

        for ($i = 0; $i < $count; $i++) {
            $currentTime = $startTime + ($i * $interval);
            $timeStr = gmdate("H:i:s", (int) $currentTime) . "." . sprintf("%03d", ($currentTime - (int) $currentTime) * 1000);

            $outputFile = $outputDir . "/frame_" . ($i + 1) . ".jpg";

            // Using -vframes 1 significantly faster than just -ss without it for single frame
            // Putting -ss before -i is faster (input seeking)
            $command = sprintf(
                'ffmpeg -y -ss %s -i %s -vframes 1 -q:v 2 %s',
                System::escapeArg($timeStr),
                System::escapeArg($video),
                System::escapeArg($outputFile)
            );

            // Suppress output for cleaner loop, check return code
            // Actually System::exec returns last line, but we can capture output in array
            $outLines = [];
            $ret = 0;
            System::exec($command, $outLines, $ret);

            if ($ret === 0 && file_exists($outputFile)) {
                echo "Extracted frame " . ($i + 1) . " at $timeStr\n";
            } else {
                echo "Failed to extract frame " . ($i + 1) . " at $timeStr\n";
            }
        }

        echo "Frame extraction complete.\n";
    }

    private static function timeToSeconds(string $time)
    {
        $parts = explode(':', $time);
        if (count($parts) === 3) {
            return ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
        } else if (count($parts) === 2) {
            return ($parts[0] * 60) + $parts[1];
        }
        return false;
    }

    public static function handleExtractVideo(): void
    {
        global $argv;

        if (count($argv) < 4) {
            echo "Usage: php cli.php media:extract-video <video_file> <output_video> [start_time] [duration]\n";
            echo "  start_time: Start time (default: 00:00:00)\n";
            echo "  duration: Duration to extract (default: full)\n";
            exit(1);
        }

        $video = $argv[2];
        $output = $argv[3];
        $start = isset($argv[4]) ? $argv[4] : '00:00:00';
        $duration = isset($argv[5]) ? $argv[5] : null;

        if (!System::isAvailable('ffmpeg')) {
            echo "Error: 'ffmpeg' tool is not found in the system PATH. Please install it.\n";
            exit(1);
        }

        if (!file_exists($video)) {
            echo "Error: Video file not found: $video\n";
            exit(1);
        }

        $cmdParts = [
            'ffmpeg',
            '-y',
            '-ss',
            System::escapeArg($start),
            '-i',
            System::escapeArg($video),
        ];

        if ($duration !== null) {
            $cmdParts[] = '-t';
            $cmdParts[] = System::escapeArg($duration);
        }

        // We re-encode by default for frame accuracy at start
        // To force copy, user would need a different command or flag, ignoring for now as per plan.

        $cmdParts[] = System::escapeArg($output);

        $command = implode(' ', $cmdParts);

        $outLines = [];
        $returnVar = 0;
        System::exec($command, $outLines, $returnVar);

        if ($returnVar !== 0) {
            echo "Error: Failed to extract video segment.\n";
            exit(1);
        }

        if (!file_exists($output)) {
            echo "Error: Output file was not created.\n";
            exit(1);
        }

        echo "Video segment extracted successfully to $output\n";
    }

    public static function handleToHls(): void
    {
        global $argv;

        if (count($argv) < 4) {
            echo "Usage: php cli.php media:to-hls <video_file> <output_dir> [segment_duration] [playlist_name]\n";
            echo "  segment_duration: Duration of each segment in seconds (default: 10)\n";
            echo "  playlist_name: Name of the m3u8 playlist file (default: playlist.m3u8)\n";
            exit(1);
        }

        $video = $argv[2];
        $outputDir = $argv[3];
        $segmentDuration = isset($argv[4]) ? (int) $argv[4] : 10;
        $playlistName = isset($argv[5]) ? $argv[5] : 'playlist.m3u8';

        if (!System::isAvailable('ffmpeg')) {
            echo "Error: 'ffmpeg' tool is not found in the system PATH. Please install it.\n";
            exit(1);
        }

        if (!file_exists($video)) {
            echo "Error: Video file not found: $video\n";
            exit(1);
        }

        if (!is_dir($outputDir)) {
            if (!mkdir($outputDir, 0755, true)) {
                echo "Error: Failed to create output directory: $outputDir\n";
                exit(1);
            }
        }

        $playlistPath = $outputDir . '/' . $playlistName;
        // Construct the segment filename pattern
        // We need to use %v or similar if we were doing multi-bitrate, but for single stream:
        // ffmpeg defaults to 'playlistname%d.ts' if using -hls_segment_filename, 
        // or effectively just naming sequence based on output if we don't specify segment filename pattern explicitly but let it default.
        // Let's specify it for clarity.

        $segmentFilename = $outputDir . '/segment_%03d.ts';

        $cmdParts = [
            'ffmpeg',
            '-y',
            '-i',
            System::escapeArg($video),
            '-c:v',
            'libx264', // Ensure compatibility
            '-c:a',
            'aac',     // Ensure compatibility
            '-f',
            'hls',
            '-hls_time',
            (string) $segmentDuration,
            '-hls_list_size',
            '0', // 0 means keep all segments in playlist (VOD style)
            '-hls_segment_filename',
            System::escapeArg($segmentFilename),
            System::escapeArg($playlistPath)
        ];

        // Ensure GOP size is aligned with segment duration for best results.
        // Usually fps * duration. Assuming 30fps roughly for now or letting ffmpeg handle it.
        // Ideally we adding '-g 30' or similar but let's stick to basic functional command first.
        // Force keyframes at segment duration? -force_key_frames "expr:gte(t,n_forced*10)"

        $command = implode(' ', $cmdParts);

        echo "Converting to HLS...\n";

        $outLines = [];
        $returnVar = 0;
        System::exec($command, $outLines, $returnVar);

        if ($returnVar !== 0) {
            echo "Error: Failed to convert to HLS.\n";
            exit(1);
        }

        if (!file_exists($playlistPath)) {
            echo "Error: Playlist file was not created.\n";
            exit(1);
        }

        echo "HLS conversion complete. Playlist: $playlistPath\n";
    }

    public static function handleFastStart(): void
    {
        global $argv;

        if (count($argv) < 4) {
            echo "Usage: php cli.php media:fast-start <input_video> <output_video>\n";
            exit(1);
        }

        $input = $argv[2];
        $output = $argv[3];

        if (!System::isAvailable('ffmpeg')) {
            echo "Error: 'ffmpeg' tool is not found in the system PATH. Please install it.\n";
            exit(1);
        }

        if (!file_exists($input)) {
            echo "Error: Input video not found: $input\n";
            exit(1);
        }

        $cmdParts = [
            'ffmpeg',
            '-y',
            '-i',
            System::escapeArg($input),
            '-c',
            'copy',
            '-movflags',
            '+faststart',
            System::escapeArg($output)
        ];

        $command = implode(' ', $cmdParts);

        echo "Optimizing video for web (Fast Start)...\n";

        $outLines = [];
        $returnVar = 0;
        System::exec($command, $outLines, $returnVar);

        if ($returnVar !== 0) {
            echo "Error: Failed to optimize video.\n";
            exit(1);
        }

        if (!file_exists($output)) {
            echo "Error: Output file was not created.\n";
            exit(1);
        }

        echo "Video optimized successfully to $output\n";
    }

    public static function handleInfo(): void
    {
        global $argv;

        if (count($argv) < 3) {
            echo "Usage: php cli.php media:info <input_file> [--raw]\n";
            exit(1);
        }

        $input = $argv[2];
        $raw = isset($argv[3]) && $argv[3] === '--raw';

        if (!System::isAvailable('ffprobe')) {
            echo "Error: 'ffprobe' tool is not found in the system PATH. Please install it.\n";
            exit(1);
        }

        if (!file_exists($input)) {
            echo "Error: Input file not found: $input\n";
            exit(1);
        }

        $command = sprintf(
            'ffprobe -v quiet -print_format json -show_format -show_streams %s',
            System::escapeArg($input)
        );

        $outLines = [];
        $returnVar = 0;
        System::exec($command, $outLines, $returnVar);

        if ($returnVar !== 0) {
            echo "Error: Failed to retrieve media info.\n";
            exit(1);
        }

        $jsonOutput = implode("\n", $outLines);

        if ($raw) {
            echo $jsonOutput . "\n";
            return;
        }

        $info = json_decode($jsonOutput, true);
        if (!$info) {
            echo "Error: Failed to parse ffprobe output.\n";
            exit(1);
        }

        echo "Media Information:\n";
        echo "------------------\n";
        echo "File: " . basename($input) . "\n";

        if (isset($info['format'])) {
            $fmt = $info['format'];
            echo "Format: " . ($fmt['format_long_name'] ?? $fmt['format_name']) . "\n";
            echo "Duration: " . (isset($fmt['duration']) ? gmdate("H:i:s", (int) $fmt['duration']) : 'N/A') . "\n";
            echo "Size: " . (isset($fmt['size']) ? self::formatBytes($fmt['size']) : 'N/A') . "\n";
            echo "Bitrate: " . (isset($fmt['bit_rate']) ? round($fmt['bit_rate'] / 1000) . ' kb/s' : 'N/A') . "\n";
        }

        if (isset($info['streams'])) {
            foreach ($info['streams'] as $stream) {
                echo "\nStream #{$stream['index']} (" . strtoupper($stream['codec_type']) . "):\n";
                echo "  Codec: " . ($stream['codec_long_name'] ?? $stream['codec_name']) . "\n";
                if ($stream['codec_type'] === 'video') {
                    echo "  Resolution: " . ($stream['width'] ?? '?') . "x" . ($stream['height'] ?? '?') . "\n";
                    echo "  FPS: " . ($stream['r_frame_rate'] ?? 'N/A') . "\n";
                } elseif ($stream['codec_type'] === 'audio') {
                    echo "  Sample Rate: " . ($stream['sample_rate'] ?? '?') . " Hz\n";
                    echo "  Channels: " . ($stream['channels'] ?? '?') . "\n";
                }
            }
        }
    }

    public static function handleGif(): void
    {
        global $argv;

        if (count($argv) < 4) {
            echo "Usage: php cli.php media:gif <video_file> <output_gif> [start_time] [duration] [width]\n";
            echo "  start_time: default 00:00:00\n";
            echo "  duration: default 5\n";
            echo "  width: default 320\n";
            exit(1);
        }

        $video = $argv[2];
        $output = $argv[3];
        $start = isset($argv[4]) ? $argv[4] : '00:00:00';
        $duration = isset($argv[5]) ? $argv[5] : '5';
        $width = isset($argv[6]) ? (int) $argv[6] : 320;

        if (!System::isAvailable('ffmpeg')) {
            echo "Error: 'ffmpeg' tool is not found in the system PATH. Please install it.\n";
            exit(1);
        }

        if (!file_exists($video)) {
            echo "Error: Video file not found: $video\n";
            exit(1);
        }

        // We use a complex filter to generate a palette and then use it.
        // Single command approach using filter_complex
        // [0:v] fps=15,scale=320:-1:flags=lanczos,split [a][b];[a] palettegen [p];[b][p] paletteuse

        $filters = "fps=15,scale={$width}:-1:flags=lanczos,split[s0][s1];[s0]palettegen[p];[s1][p]paletteuse";

        $cmdParts = [
            'ffmpeg',
            '-y',
            '-ss',
            System::escapeArg($start),
            '-t',
            System::escapeArg($duration),
            '-i',
            System::escapeArg($video),
            '-vf',
            System::escapeArg($filters),
            System::escapeArg($output)
        ];

        $command = implode(' ', $cmdParts);

        echo "Generating high-quality GIF...\n";

        $outLines = [];
        $returnVar = 0;
        System::exec($command, $outLines, $returnVar);

        if ($returnVar !== 0) {
            echo "Error: Failed to generate GIF.\n";
            exit(1);
        }

        if (!file_exists($output)) {
            echo "Error: Output GIF was not created.\n";
            exit(1);
        }

        echo "GIF generated successfully to $output\n";
    }

    public static function handleWatermark(): void
    {
        global $argv;

        if (count($argv) < 5) {
            echo "Usage: php cli.php media:watermark <video_file> <output_video> <watermark_image> [position] [padding]\n";
            echo "  position: top-left, top-right, bottom-left, bottom-right, center (default: bottom-right)\n";
            echo "  padding: pixels (default: 10)\n";
            exit(1);
        }

        $video = $argv[2];
        $output = $argv[3];
        $watermark = $argv[4];
        $position = isset($argv[5]) ? $argv[5] : 'bottom-right';
        $padding = isset($argv[6]) ? (int) $argv[6] : 10;

        if (!System::isAvailable('ffmpeg')) {
            echo "Error: 'ffmpeg' tool is not found in the system PATH. Please install it.\n";
            exit(1);
        }

        if (!file_exists($video)) {
            echo "Error: Video file not found: $video\n";
            exit(1);
        }

        if (!file_exists($watermark)) {
            echo "Error: Watermark image not found: $watermark\n";
            exit(1);
        }

        // Calculate overlay coordinates
        $overlayStr = "";
        switch ($position) {
            case 'top-left':
                $overlayStr = "x={$padding}:y={$padding}";
                break;
            case 'top-right':
                $overlayStr = "x=main_w-overlay_w-{$padding}:y={$padding}";
                break;
            case 'bottom-left':
                $overlayStr = "x={$padding}:y=main_h-overlay_h-{$padding}";
                break;
            case 'center':
                $overlayStr = "x=(main_w-overlay_w)/2:y=(main_h-overlay_h)/2";
                break;
            case 'bottom-right':
            default:
                $overlayStr = "x=main_w-overlay_w-{$padding}:y=main_h-overlay_h-{$padding}";
                break;
        }

        $cmdParts = [
            'ffmpeg',
            '-y',
            '-i',
            System::escapeArg($video),
            '-i',
            System::escapeArg($watermark),
            '-filter_complex',
            System::escapeArg("overlay={$overlayStr}"),
            '-c:a',
            'copy', // Copy audio codec
            System::escapeArg($output)
        ];

        $command = implode(' ', $cmdParts);

        echo "Applying watermark to video...\n";

        $outLines = [];
        $returnVar = 0;
        System::exec($command, $outLines, $returnVar);

        if ($returnVar !== 0) {
            echo "Error: Failed to apply watermark.\n";
            exit(1);
        }

        if (!file_exists($output)) {
            echo "Error: Output video was not created.\n";
            exit(1);
        }

        echo "Watermark applied successfully to $output\n";
    }

    public static function handleCompress(): void
    {
        global $argv;

        if (count($argv) < 4) {
            echo "Usage: php cli.php media:compress <video_file> <output_video> [crf]\n";
            echo "  crf: Constant Rate Factor (0-51, default: 28). Lower is better quality/larger size.\n";
            exit(1);
        }

        $video = $argv[2];
        $output = $argv[3];
        $crf = isset($argv[4]) ? (int) $argv[4] : 28;

        if (!System::isAvailable('ffmpeg')) {
            echo "Error: 'ffmpeg' tool is not found in the system PATH. Please install it.\n";
            exit(1);
        }

        if (!file_exists($video)) {
            echo "Error: Video file not found: $video\n";
            exit(1);
        }

        // Validate CRF range
        if ($crf < 0 || $crf > 51) {
            echo "Error: CRF must be between 0 and 51.\n";
            exit(1);
        }

        $cmdParts = [
            'ffmpeg',
            '-y',
            '-i',
            System::escapeArg($video),
            '-vcodec',
            'libx264',
            '-crf',
            (string) $crf,
            System::escapeArg($output)
        ];

        $command = implode(' ', $cmdParts);

        echo "Compressing video (CRF $crf)...\n";

        $outLines = [];
        $returnVar = 0;
        System::exec($command, $outLines, $returnVar);

        if ($returnVar !== 0) {
            echo "Error: Failed to compress video.\n";
            exit(1);
        }

        if (!file_exists($output)) {
            echo "Error: Output video was not created.\n";
            exit(1);
        }

        echo "Video compressed successfully to $output\n";
    }


}
