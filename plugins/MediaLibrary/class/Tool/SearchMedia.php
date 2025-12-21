<?php

namespace MediaLibrary\Tool;

use McpServer\Tool\BaseTool;
use MediaLibrary\Service\MediaLibraryService;
use GaiaAlpha\Session;

class SearchMedia extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'search_media',
            'description' => 'Search media files by filename, alt text, or caption',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'site' => [
                        'type' => 'string',
                        'description' => 'Site domain (default: default)'
                    ],
                    'query' => [
                        'type' => 'string',
                        'description' => 'Search query'
                    ]
                ],
                'required' => ['query']
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

        $query = $arguments['query'];
        $results = MediaLibraryService::searchMedia($query, $userId);

        $output = "# Search Results for: \"$query\"\n\n";
        $output .= "Found " . count($results) . " file(s)\n\n";

        if (empty($results)) {
            $output .= "No files matched your search.\n";
        } else {
            foreach ($results as $file) {
                $output .= "## {$file['original_filename']}\n";
                $output .= "- **ID**: {$file['id']}\n";
                $output .= "- **Filename**: {$file['filename']}\n";
                $output .= "- **Type**: {$file['mime_type']}\n";

                if (!empty($file['alt_text'])) {
                    $output .= "- **Alt Text**: {$file['alt_text']}\n";
                }

                if (!empty($file['caption'])) {
                    $output .= "- **Caption**: {$file['caption']}\n";
                }

                if (!empty($file['tags'])) {
                    $output .= "- **Tags**: {$file['tags']}\n";
                }

                $output .= "- **URL**: /media/{$userId}/{$file['filename']}\n\n";
            }
        }

        return $this->resultText($output);
    }
}
