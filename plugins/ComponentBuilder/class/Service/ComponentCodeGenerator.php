<?php


namespace ComponentBuilder\Service;


class ComponentCodeGenerator
{
  public function generate(array $definition): string
  {
    $name = $definition['name'];
    $title = $definition['title'];
    $layout = $definition['layout'];

    // Scan layout for custom components
    $customComponents = $this->findCustomComponents($layout);

    // Generate imports for custom components
    $customImports = "";
    $customComponentsList = "";

    foreach ($customComponents as $viewName) {
      $className = $this->pascalCase($viewName);
      // Note: We assume the generated files exist in defined path
      // Using relative path from where these components are usually served 
      // (resources/js/components/custom/*.js) relative to resources/js/components/custom/
      // Actually, if we are in resources/js/components/custom/, imports are ./ClassName.js
      $customImports .= "const {$className} = defineAsyncComponent(() => import('./" . preg_replace('/[^a-zA-Z0-9_-]/', '', $viewName) . ".js'));\n";
      $customComponentsList .= "    {$className},\n";
    }

    // Generate JS Module format compatible with browser ES imports
    $template = <<<JS
import { ref, onMounted, defineAsyncComponent } from 'vue';

// Import Library Components
const StatCard = defineAsyncComponent(() => import('../builder/library/StatCard.js'));
const DataTable = defineAsyncComponent(() => import('../builder/library/DataTable.js'));
const FormInput = defineAsyncComponent(() => import('../builder/library/FormInput.js'));
const FormSelect = defineAsyncComponent(() => import('../builder/library/FormSelect.js'));
const FormButton = defineAsyncComponent(() => import('../builder/library/FormButton.js'));
const ChartWidget = defineAsyncComponent(() => import('../builder/library/ChartWidget.js'));
const LayoutContainer = defineAsyncComponent(() => import('../builder/library/LayoutContainer.js'));
const LayoutRow = defineAsyncComponent(() => import('../builder/library/LayoutRow.js'));
const LayoutCol = defineAsyncComponent(() => import('../builder/library/LayoutCol.js'));
const ActionButton = defineAsyncComponent(() => import('../builder/library/ActionButton.js'));
const LinkButton = defineAsyncComponent(() => import('../builder/library/LinkButton.js'));

// Custom Component Imports
{$customImports}

export default {
  name: '{$this->pascalCase($name)}',
  components: {
    StatCard,
    DataTable,
    FormInput,
    FormSelect,
    FormButton,
    ChartWidget,
    LayoutContainer,
    LayoutRow,
    LayoutCol,
    ActionButton,
    LinkButton,
{$customComponentsList}
  },
  template: `
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
  `,
  setup() {
    const loading = ref(false);
    
    // Data sources
    const data = ref({});
    const formData = ref({});

    onMounted(async () => {
        console.log('Component {$name} mounted');
        await restoreData();
    });
    
    const restoreData = async () => {
        // Fetch data based on config
        loading.value = true;
        // Mock data loading
        setTimeout(() => {
            data.value = {
                chartData: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
                    datasets: [
                        {
                            label: 'Sales',
                            data: [12, 19, 3, 5, 2],
                            backgroundColor: 'rgba(99, 102, 241, 0.5)',
                            borderColor: '#6366f1',
                            borderWidth: 1
                        }
                    ]
                }
            };
            loading.value = false;
        }, 500);
    };

    const submitForm = async () => {
        console.log('Form Submitted', formData.value);
        loading.value = true;
        await new Promise(r => setTimeout(r, 1000));
        loading.value = false;
        alert('Form submitted! check console');
    };

    const handleAction = async (action) => {
        console.log('Action Triggered:', action);
        switch (action) {
            case 'refresh':
                await restoreData();
                break;
            case 'back':
                window.history.back();
                break;
            default:
                alert('Action: ' + action);
        }
    };
    
    return {
        loading,
        data,
        formData,
        submitForm,
        handleAction
    };
  }
};
JS;

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
        $value = $component['props']['value'] ?? '0';
        return "<StatCard label=\"{$label}\" :value=\"'{$value}'\" :loading=\"loading\" />";
      case 'data-table':
        $endpoint = $component['props']['endpoint'] ?? '';
        return "<DataTable :columns=\"[]\" :data=\"[]\" :loading=\"loading\" endpoint=\"{$endpoint}\" />";
      case 'chart-bar':
        $title = $component['props']['title'] ?? $label;
        return "<ChartWidget type=\"bar\" title=\"{$title}\" :data=\"data.chartData || {labels:[],datasets:[]}\" :loading=\"loading\" />";
      case 'chart-line':
        $title = $component['props']['title'] ?? $label;
        return "<ChartWidget type=\"line\" title=\"{$title}\" :data=\"data.chartData || {labels:[],datasets:[]}\" :loading=\"loading\" />";
      case 'container':
        $fluid = ($component['props']['fluid'] ?? false) ? 'true' : 'false';
        return "<LayoutContainer :fluid=\"{$fluid}\">" . $this->generateChildren($component['children'] ?? []) . "</LayoutContainer>";
      case 'row':
        $gutter = $component['props']['gutter'] ?? 'md';
        $align = $component['props']['align'] ?? 'start';
        $justify = $component['props']['justify'] ?? 'start';
        return "<LayoutRow gutter=\"{$gutter}\" align=\"{$align}\" justify=\"{$justify}\">" . $this->generateChildren($component['children'] ?? []) . "</LayoutRow>";
      case 'col':
        $width = $component['props']['width'] ?? 12;
        return "<LayoutCol :width=\"{$width}\">" . $this->generateChildren($component['children'] ?? []) . "</LayoutCol>";
      case 'form':
        return "<form @submit.prevent=\"submitForm\">" . $this->generateChildren($component['children'] ?? []) . "</form>";
      case 'input':
        $name = $component['props']['name'] ?? 'field_' . uniqid();
        $inputType = $component['props']['type'] ?? 'text';
        $placeholder = $component['props']['placeholder'] ?? '';
        // Add to reactive data model logic (implicit TODO)
        return "<FormInput name=\"{$name}\" label=\"{$label}\" type=\"{$inputType}\" placeholder=\"{$placeholder}\" v-model=\"formData.{$name}\" />";
      case 'select':
        $name = $component['props']['name'] ?? 'field_' . uniqid();
        $options = json_encode($component['props']['options'] ?? []);
        // We need to escape double quotes for the HTML attribute
        $optionsAttr = htmlspecialchars($options, ENT_QUOTES, 'UTF-8');
        return "<FormSelect name=\"{$name}\" label=\"{$label}\" :options=\"{$optionsAttr}\" v-model=\"formData.{$name}\" />";
      case 'button':
        $btnType = $component['props']['type'] ?? 'button';
        $variant = $component['props']['variant'] ?? 'primary';
        return "<FormButton label=\"{$label}\" type=\"{$btnType}\" variant=\"{$variant}\" :loading=\"loading\" />";
      case 'action-button':
        $action = $component['props']['action'] ?? 'refresh';
        $variant = $component['props']['variant'] ?? 'primary';
        return "<ActionButton label=\"{$label}\" action=\"{$action}\" variant=\"{$variant}\" @action=\"handleAction\" />";
      case 'link-button':
        $href = $component['props']['href'] ?? '#';
        $target = $component['props']['target'] ?? '_self';
        $variant = $component['props']['variant'] ?? 'secondary';
        return "<LinkButton label=\"{$label}\" href=\"{$href}\" target=\"{$target}\" variant=\"{$variant}\" />";
      case 'markdown':
        $content = $component['props']['content'] ?? '';
        $parsedown = new \GaiaAlpha\Helper\Parsedown();
        $html = $parsedown->text($content);
        // Escape backticks and template literals for Vue template string compatibility
        $html = str_replace(['`', '${'], ['\`', '\${'], $html);
        return "<div class=\"markdown-content\">{$html}</div>";
      default:
        // Check for custom component prefix
        if (strpos($type, 'custom:') === 0) {
          $viewName = substr($type, 7);
          $tagName = $this->pascalCase($viewName);
          return "<{$tagName} />";
        }
        return "<div class=\"component-{$type}\">Component: {$type} ({$label})</div>";
    }
  }

  private function generateChildren(array $children)
  {
    $html = "";
    foreach ($children as $child) {
      if (is_array($child)) {
        $html .= $this->generateComponent($child);
      }
    }
    return $html;
  }
  private function pascalCase($string)
  {
    return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $string)));
  }

  private function findCustomComponents($layout)
  {
    $components = [];
    $this->traverseLayout($layout, function ($node) use (&$components) {
      if (isset($node['type']) && strpos($node['type'], 'custom:') === 0) {
        $viewName = substr($node['type'], 7);
        if (!in_array($viewName, $components)) {
          $components[] = $viewName;
        }
      }
    });
    return $components;
  }

  private function traverseLayout($node, $callback)
  {
    $callback($node);
    if (isset($node['children']) && is_array($node['children'])) {
      foreach ($node['children'] as $child) {
        $this->traverseLayout($child, $callback);
      }
    }
  }
}
