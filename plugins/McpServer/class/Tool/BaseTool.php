<?php

namespace McpServer\Tool;

abstract class BaseTool
{
    /**
     * Get the tool definition for tools/list
     * 
     * @return array
     */
    abstract public function getDefinition(): array;

    /**
     * Execute the tool logic
     * 
     * @param array $arguments
     * @return array
     */
    abstract public function execute(array $arguments): array;

    /**
     * Helper to return a text response
     */
    protected function resultText($text)
    {
        return [
            'content' => [
                ['type' => 'text', 'text' => $text]
            ]
        ];
    }

    /**
     * Helper to return a JSON response
     */
    protected function resultJson($data)
    {
        return $this->resultText(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
