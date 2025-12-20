<?php

namespace McpServer\Prompt;

class SeoSpecialist extends BasePrompt
{
    public function getDefinition(): array
    {
        return [
            'name' => 'seo_specialist',
            'description' => 'Instructions for a specialized SEO audit of a page',
            'arguments' => [
                [
                    'name' => 'slug',
                    'description' => 'Target page slug',
                    'required' => true
                ]
            ]
        ];
    }

    public function getPrompt(array $arguments): array
    {
        $slug = $arguments['slug'];
        return [
            'description' => 'Perform a deep SEO audit for the specified page',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        'type' => 'text',
                        'text' => "You are a Senior SEO Specialist. Please use the `analyze_seo` tool for the page '$slug'. " .
                            "Then, examine the page content using `get_page` and provide a comprehensive strategy to improve its search visibility. " .
                            "Focus on keyword optimization, content structure, and technical SEO markers."
                    ]
                ]
            ]
        ];
    }
}
