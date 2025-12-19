<?php

namespace McpServer\Tool;

class GenerateTemplateSchema extends BaseTool
{
    public function getDefinition(): array
    {
        return [
            'name' => 'generate_template_schema',
            'description' => 'Generate or suggest template metadata and configuration based on description',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'description' => ['type' => 'string', 'description' => 'Natural language description of the template (e.g. "A blog post with a hero image and sidebar")']
                ],
                'required' => ['description']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $desc = strtolower($arguments['description']);

        $schema = [
            'title' => 'Suggested Template',
            'slug' => 'suggested-template',
            'fields' => []
        ];

        if (strpos($desc, 'blog') !== false || strpos($desc, 'post') !== false) {
            $schema['title'] = 'Blog Post';
            $schema['slug'] = 'blog-post';
            $schema['fields'][] = ['name' => 'title', 'type' => 'text', 'label' => 'Post Title'];
            $schema['fields'][] = ['name' => 'content', 'type' => 'richtext', 'label' => 'Post Content'];
            $schema['fields'][] = ['name' => 'author', 'type' => 'text', 'label' => 'Author Name'];
        }

        if (strpos($desc, 'image') !== false || strpos($desc, 'hero') !== false) {
            $schema['fields'][] = ['name' => 'hero_image', 'type' => 'image', 'label' => 'Hero Image'];
        }

        if (strpos($desc, 'sidebar') !== false) {
            $schema['fields'][] = ['name' => 'sidebar_content', 'type' => 'richtext', 'label' => 'Sidebar Content'];
        }

        if (empty($schema['fields'])) {
            $schema['fields'] = [
                ['name' => 'title', 'type' => 'text', 'label' => 'Page Title'],
                ['name' => 'body', 'type' => 'richtext', 'label' => 'Page Body']
            ];
        }

        return $this->resultJson([
            'suggested_schema' => $schema,
            'explanation' => "Based on your description '$desc', I suggested a schema with " . count($schema['fields']) . " fields."
        ]);
    }
}
