<?php

namespace McpServer\Tool;

use GaiaAlpha\Env;
use GaiaAlpha\File;

class AiGenerateImage extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'ai_generate_image',
            'description' => 'Simulate AI image generation and save to site assets',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'site' => ['type' => 'string', 'description' => 'Site domain (default: default)'],
                    'prompt' => ['type' => 'string', 'description' => 'Description of the image to generate'],
                    'filename' => ['type' => 'string', 'description' => 'Target filename (e.g. hero.jpg)']
                ],
                'required' => ['prompt', 'filename']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $site = $arguments['site'] ?? 'default';
        $filename = $arguments['filename'];
        $rootDir = Env::get('root_dir');
        $assetsDir = ($site === 'default') ? $rootDir . '/my-data/assets' : $rootDir . '/my-data/sites/' . $site . '/assets';

        if (!is_dir($assetsDir)) {
            mkdir($assetsDir, 0755, true);
        }

        // Simulate generation by fetching a random image based on prompt (using picsum with a seed from prompt)
        $seed = md5($arguments['prompt']);
        $url = "https://picsum.photos/seed/$seed/800/600";

        $content = @file_get_contents($url);
        if ($content === false) {
            throw new \Exception("Failed to 'generate' (fetch) image from placeholder service. Check your internet connection.");
        }

        $targetPath = $assetsDir . '/' . $filename;
        file_put_contents($targetPath, $content);

        return $this->resultText("Image generated and saved to assets as '$filename' for site '$site'.");
    }
}
