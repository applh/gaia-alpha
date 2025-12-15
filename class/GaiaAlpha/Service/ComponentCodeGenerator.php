<?php

namespace GaiaAlpha\Service;

class ComponentCodeGenerator
{
  public function generate(array $definition): string
  {
    $name = $definition['name'];
    $title = $definition['title'];
    $layout = $definition['layout'];

    // Basic template
    $template = <<<VUE
<template>
  <div class="admin-page">
    <div class="admin-header">
      <h2 class="page-title">{$title}</h2>
    </div>
    <div class="admin-content">
      <!-- Layout Container -->
      <div class="component-container">
        <!-- Components will be rendered here based on layout -->
        {$this->generateLayout($layout)}
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, defineAsyncComponent } from 'vue';

// Import Library Components
const StatCard = defineAsyncComponent(() => import('../../builder/library/StatCard.js'));
const DataTable = defineAsyncComponent(() => import('../../builder/library/DataTable.js'));

export default {
  name: '{$this->pascalCase($name)}',
  components: {
    StatCard,
    DataTable
  },
  setup() {
    const loading = ref(false);
    
    // Data sources
    const data = ref({});
    
    onMounted(async () => {
        console.log('Component {$name} mounted');
        await restoreData();
    });
    
    const restoreData = async () => {
        // Fetch data based on config
        loading.value = true;
        // Mock data loading
        setTimeout(() => {
            loading.value = false;
        }, 500);
    };
    
    return {
        loading,
        data
    };
  }
};
</script>

<style scoped>
.component-container {
    padding: 20px;
}
</style>
VUE;

    return $template;
  }

  private function generateLayout($layout)
  {
    // Recursive layout generation
    $type = $layout['type'] ?? 'div';
    $children = $layout['children'] ?? [];

    $html = "";

    if ($type === 'container' || $type === 'div') {
      $html .= "<div class=\"layout-{$type}\">";
      if (!empty($children)) {
        foreach ($children as $child) {
          if (is_array($child)) {
            $html .= $this->generateComponent($child);
          }
        }
      }
      $html .= "</div>";
    } else {
      // It's a component at root level?
      $html .= $this->generateComponent($layout);
    }

    return $html;
  }

  private function generateComponent($component)
  {
    $type = $component['type'] ?? 'unknown';
    $label = $component['label'] ?? '';

    // Map types to components
    switch ($type) {
      case 'stat-card':
        return "<StatCard label=\"{$label}\" :value=\"1234\" :loading=\"loading\" />";
      case 'data-table':
        return "<DataTable :columns=\"[]\" :data=\"[]\" :loading=\"loading\" />";
      default:
        return "<div class=\"component-{$type}\">Component: {$type} ({$label})</div>";
    }
  }

  private function pascalCase($string)
  {
    return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $string)));
  }
}
