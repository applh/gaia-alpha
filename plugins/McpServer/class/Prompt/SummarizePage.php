<?php

namespace McpServer\Prompt;

class SummarizePage extends BasePrompt
{
    public function getDefinition(): array
    {
        return [
            'name' => 'summarize_page',
            'description' => 'Summarize the content of a page',
            'arguments' => [
                [
                    'name' => 'slug',
                    'description' => 'Slug of the page to summarize',
                    'required' => true
                ]
            ]
        ];
    }

    public function getPrompt(array $arguments): array
    {
        $slug = $arguments['slug'] ?? 'home';
        return [
            'description' => 'Summarize the content of a page',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        'type' => 'text',
                        'text' => "Please summarize the content of the page with slug '$slug'. You can use the 'get_page' tool to retrieve the content first."
                    ]
                ]
            ]
        ];
    }
}
