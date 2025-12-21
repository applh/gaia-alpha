<?php

namespace MediaLibrary\Tool;

use McpServer\Tool\BaseTool;
use MediaLibrary\Service\MediaLibraryService;
use GaiaAlpha\Session;

class TagMedia extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'tag_media',
            'description' => 'Assign or remove tags from media files',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'site' => [
                        'type' => 'string',
                        'description' => 'Site domain (default: default)'
                    ],
                    'media_id' => [
                        'type' => 'integer',
                        'description' => 'ID of the media file'
                    ],
                    'tag_ids' => [
                        'type' => 'array',
                        'items' => ['type' => 'integer'],
                        'description' => 'Array of tag IDs to assign'
                    ],
                    'tag_names' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'description' => 'Array of tag names (will create if not exists)'
                    ]
                ],
                'required' => ['media_id']
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

        $mediaId = $arguments['media_id'];

        // Verify media exists and belongs to user
        $media = MediaLibraryService::getMediaById($mediaId);
        if (!$media) {
            throw new \Exception("Media file not found: $mediaId");
        }

        if ($media['user_id'] != $userId) {
            throw new \Exception("Access denied to media file: $mediaId");
        }

        $tagIds = [];

        // Handle tag IDs
        if (!empty($arguments['tag_ids'])) {
            $tagIds = array_merge($tagIds, $arguments['tag_ids']);
        }

        // Handle tag names (create if needed)
        if (!empty($arguments['tag_names'])) {
            $allTags = MediaLibraryService::getAllTags();
            $tagMap = [];
            foreach ($allTags as $tag) {
                $tagMap[strtolower($tag['name'])] = $tag['id'];
            }

            foreach ($arguments['tag_names'] as $tagName) {
                $key = strtolower($tagName);
                if (isset($tagMap[$key])) {
                    $tagIds[] = $tagMap[$key];
                } else {
                    // Create new tag
                    $newTagId = MediaLibraryService::createTag($tagName);
                    $tagIds[] = $newTagId;
                }
            }
        }

        // Assign tags
        $success = MediaLibraryService::assignTags($mediaId, array_unique($tagIds));

        if ($success) {
            $updatedMedia = MediaLibraryService::getMediaById($mediaId);
            $tagNames = array_map(fn($t) => $t['name'], $updatedMedia['tags']);

            return $this->resultText(
                "Successfully tagged media file '{$media['original_filename']}' (ID: $mediaId)\n" .
                "Tags: " . implode(', ', $tagNames)
            );
        } else {
            throw new \Exception("Failed to assign tags to media file: $mediaId");
        }
    }
}
