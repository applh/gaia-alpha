<?php

namespace GaiaAlpha\Service;

use GaiaAlpha\Model\AdminComponent;

class AdminComponentManager
{
    public function getComponents()
    {
        return AdminComponent::findAll();
    }

    public function getComponent($id)
    {
        return AdminComponent::findById($id);
    }

    public function createComponent($data)
    {
        // Validation could go here
        return AdminComponent::create($data);
    }

    public function updateComponent($id, $data)
    {
        return AdminComponent::update($id, $data);
    }

    public function deleteComponent($id)
    {
        return AdminComponent::delete($id);
    }

    public function generateCode($id)
    {
        $component = $this->getComponent($id);
        if (!$component) {
            throw new \Exception("Component not found");
        }

        $definition = json_decode($component['definition'], true);
        if (!$definition) {
            throw new \Exception("Invalid component definition");
        }

        // Logic to generate Vue code from definition
        $generator = new ComponentCodeGenerator();
        $code = $generator->generate($definition);

        // Save generated code to DB
        AdminComponent::update($id, ['generated_code' => $code]);

        // Also save to file system (Phase 1.3 requirement)
        // clean up view_name to be safe filename
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '', $component['view_name']) . '.js';
        $path = __DIR__ . '/../../../resources/js/components/custom/' . $filename;

        // Ensure directory exists
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $code);

        return $code;
    }
}
