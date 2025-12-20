<?php

namespace McpServer\Prompt;

class UiDesigner extends BasePrompt
{
    public function getDefinition(): array
    {
        return [
            'name' => 'ui_designer',
            'description' => 'Instructions for evaluating UI components and templates',
            'arguments' => [
                [
                    'name' => 'path',
                    'description' => 'Path to the component or template (e.g. \"home_template\")',
                    'required' => true
                ]
            ]
        ];
    }

    public function getPrompt(array $arguments): array
    {
        $path = $arguments['path'];
        // Heuristic: templates are usually single names, components have extensions
        $uri = str_contains($path, '.') ? "cms://components/$path" : "cms://templates/$path";

        return [
            'description' => 'Evaluate a UI component or template against design standards',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        'type' => 'text',
                        'text' => "You are a lead UI/UX Designer. Read the source code of '$path' using the resource URI '$uri'.\n" .
                            "Evaluate the implementation against modern web standards, accessibility (WCAG), and consistency with a professional design system.\n" .
                            "Suggest improvements for visual hierarchy, contrast, and interactive states."
                    ]
                ]
            ]
        ];
    }
}
