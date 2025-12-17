<?php


namespace ComponentBuilder\Service;

use GaiaAlpha\File;
use ComponentBuilder\Model\ComponentBuilderModel;


class ComponentBuilderManager
{
    public function getComponents()
    {
        return ComponentBuilderModel::findAll();
    }

    public function getComponent($id)
    {
        return ComponentBuilderModel::findById($id);
    }

    public function createComponent($data)
    {
        // Validation could go here
        return ComponentBuilderModel::create($data);
    }

    public function updateComponent($id, $data)
    {
        return ComponentBuilderModel::update($id, $data);
    }

    public function deleteComponent($id)
    {
        return ComponentBuilderModel::delete($id);
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
        ComponentBuilderModel::update($id, ['generated_code' => $code]);

        // Also save to file system (Phase 1.3 requirement)
        // clean up view_name to be safe filename
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '', $component['view_name']) . '.js';

        // Use my-data/components/custom instead of resources
        $baseDir = \GaiaAlpha\Env::get('path_data') . '/components/custom';
        $path = $baseDir . '/' . $filename;

        // Ensure directory exists
        File::makeDirectory($baseDir);

        File::write($path, $code);

        return $code;
    }
}
