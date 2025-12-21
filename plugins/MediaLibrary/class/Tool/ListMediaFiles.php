<?php

namespace MediaLibrary\Tool;

use McpServer\Tool\BaseTool;
use MediaLibrary\Service\MediaLibraryService;
use GaiaAlpha\Session;

class ListMediaFiles extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'list_media_files',
            'description' => 'List all media files in the library with optional filtering by tags or search query',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'site' => [
                        'type' => 'string',
                        'description' => 'Site domain (default: default)'
                    ],
                    'tag' => [
                        'type' => 'string',
                        'description' => 'Filter by tag slug'
                    ],
                    'search' => [
                        'type' => 'string',
                        'description' => 'Search query for filename or metadata'
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'description' => 'Maximum number of files to return'
                    ]
                ],
                'required' => []
            ]
        ];
    }

    public function execute(array $arguments): array
    {


        // Get current user
        $userId = Session::id();
        if (!$userId) {
            throw new \Exception('Authentication required');
        }

        $filters = [
            'tag' => $arguments['tag'] ?? null,
            'search' => $arguments['search'] ?? null,
            'limit' => $arguments['limit'] ?? null
        ];

        $files = MediaLibraryService::getAllMedia($userId, array_filter($filters));

        $output = "# Media Library Files\n\n";
        $output .= "Total files: " . count($files) . "\n\n";

        if (empty($files)) {
            $output .= "No files found.\n";
        } else {
            foreach ($files as $file) {
                $output .= "## {$file['original_filename']}\n";
                $output .= "- **ID**: {$file['id']}\n";
                $output .= "- **Filename**: {$file['filename']}\n";
                $output .= "- **Type**: {$file['mime_type']}\n";
                $output .= "- **Size**: " . $this->formatBytes($file['file_size']) . "\n";

                if ($file['width'] && $file['height']) {
                    $output .= "- **Dimensions**: {$file['width']}x{$file['height']}\n";
                }

                if (!empty($file['tags'])) {
                    $output .= "- **Tags**: {$file['tags']}\n";
                }

                if (!empty($file['alt_text'])) {
                    $output .= "- **Alt Text**: {$file['alt_text']}\n";
                }

                if (!empty($file['caption'])) {
                    $output .= "- **Caption**: {$file['caption']}\n";
                }

                $output .= "- **Created**: {$file['created_at']}\n";
                $output .= "- **URL**: /media/{$userId}/{$file['filename']}\n\n";
            }
        }

        return $this->resultText($output);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
