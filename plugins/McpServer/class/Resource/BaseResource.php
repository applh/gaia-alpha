<?php

namespace McpServer\Resource;

abstract class BaseResource
{
    /**
     * Get the resource definition for resources/list
     * 
     * @return array
     */
    abstract public function getDefinition(): array;

    /**
     * Check if this resource handles the given URI
     * 
     * @param string $uri
     * @return array|null Returns matches if matched, or null
     */
    abstract public function matches(string $uri): ?array;

    /**
     * Read the resource content
     * 
     * @param string $uri
     * @param array $matches
     * @return array Response structure for resources/read
     */
    abstract public function read(string $uri, array $matches): array;

    protected function contents($uri, $text, $mimeType = 'application/json')
    {
        return [
            'contents' => [
                [
                    'uri' => $uri,
                    'mimeType' => $mimeType,
                    'text' => $text
                ]
            ]
        ];
    }
}
