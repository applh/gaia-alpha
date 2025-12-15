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
const Todos = defineAsyncComponent(() => import('./todos.js'));


export default {
  name: 'Pppp',
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
    Todos,

  },
  template: `
  <div class="admin-page">
    <div class="admin-header">
      <h2 class="page-title">ppppp</h2>
    </div>
    <div class="admin-content">
      <!-- Layout Container -->
      <div class="component-container">
        <!-- Components will be rendered here based on layout -->
        <div class="layout-container"><LayoutRow gutter="md" align="start" justify="start"><LayoutCol :width="6"><form @submit.prevent="submitForm"><FormInput name="field_69406d37c10d6" label="New input" type="text" placeholder="" v-model="formData.field_69406d37c10d6" /><FormButton label="New button" type="button" variant="primary" :loading="loading" /></form></LayoutCol><LayoutCol :width="6"><Todos /></LayoutCol></LayoutRow></div>
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
        console.log('Component pppp mounted');
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