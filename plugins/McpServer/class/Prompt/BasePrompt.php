<?php

namespace McpServer\Prompt;

abstract class BasePrompt
{
    /**
     * Get the prompt definition for prompts/list
     * 
     * @return array
     */
    abstract public function getDefinition(): array;

    /**
     * Get the prompt messages for prompts/get
     * 
     * @param array $arguments
     * @return array
     */
    abstract public function getPrompt(array $arguments): array;
}
