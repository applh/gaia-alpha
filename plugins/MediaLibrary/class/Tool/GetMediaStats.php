<?php

namespace MediaLibrary\Tool;

use McpServer\Tool\BaseTool;
use MediaLibrary\Service\MediaLibraryService;
use GaiaAlpha\Auth;

class GetMediaStats extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'get_media_stats',
            'description' => 'Get statistics about the media library including total files, size, and file types',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'site' => [
                        'type' => 'string',
                        'description' => 'Site domain (default: default)'
                    ]
                ],
                'required' => []
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $service = new MediaLibraryService();

        // Get current user
        $user = Auth::user();
        if (!$user) {
            throw new \Exception('Authentication required');
        }

        $stats = $service->getStats($user['id']);

        $output = "# Media Library Statistics\n\n";
        $output .= "- **Total Files**: {$stats['total_files']}\n";
        $output .= "- **Total Size**: {$stats['total_size_formatted']}\n\n";

        if (!empty($stats['file_types'])) {
            $output .= "## File Types\n\n";
            foreach ($stats['file_types'] as $type) {
                $output .= "- **{$type['mime_type']}**: {$type['count']} file(s)\n";
            }
        }

        return $this->resultText($output);
    }
}
