import { ref, watch, computed } from 'vue';

const SlotCard = {
    props: ['node'],
    template: `
        <div class="slot-card" style="background: var(--card-bg); border: 1px solid var(--border-color); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                <label style="font-weight:bold; color:var(--text-primary);">{{ node.slotName }}</label>
                <span style="font-size:0.8em; color:var(--text-secondary); text-transform:uppercase;">{{ node.type }}</span>
            </div>
            
            <!-- Text Content -->
            <div v-if="['h1','h2','h3','p'].includes(node.type)">
                <textarea v-model="node.content" rows="3" style="width:100%; padding:8px; background:rgba(255,255,255,0.05); border:1px solid var(--border-color); color:var(--text-primary); border-radius:4px;"></textarea>
            </div>

            <!-- Image Content -->
            <div v-if="node.type === 'image'">
                <div style="display:flex; gap:10px; align-items:center;">
                    <div style="width:60px; height:60px; background:#333; border-radius:4px; overflow:hidden; flex-shrink:0;">
                         <img v-if="node.src" :src="node.src" style="width:100%; height:100%; object-fit:cover;" />
                    </div>
                    <input type="text" v-model="node.src" placeholder="Image URL..." style="flex:1; padding:8px; background:rgba(255,255,255,0.05); border:1px solid var(--border-color); color:var(--text-primary); border-radius:4px;">
                </div>
            </div>

            <!-- Future: Containers -->
            <div v-if="['section', 'div', 'main', 'header', 'footer'].includes(node.type)" style="font-size:0.8em; color:var(--text-muted);">
                Container Slot (Drop content here - Coming Soon)
            </div>
        </div>
    `
};

export default {
    components: { SlotCard },
    props: ['modelValue'],
    emits: ['update:modelValue'],
    template: `
        <div class="slot-editor" style="display:flex; gap:20px;">
            <!-- Slots List -->
            <div class="slots-list" style="flex:1; max-width:600px;">
                <h4 style="margin-bottom:20px; color:var(--text-secondary); text-transform:uppercase; font-size:0.9em;">Template Slots</h4>
                
                <div v-if="slots.length === 0" style="padding:20px; background:var(--card-bg); border-radius:8px; border:1px dashed var(--border-color); text-align:center; color:var(--text-secondary);">
                    No named slots found in this template.<br>
                    <small>Edit the template structure to add named slots.</small>
                </div>

                <SlotCard v-for="(slot, idx) in slots" :key="idx" :node="slot" />
            </div>

            <!-- Preview / Help -->
            <div class="helper-panel" style="width:300px; padding:20px; background:rgba(0,0,0,0.1); border-left:1px solid var(--border-color);">
                <h5 style="margin-top:0;">Quick Tips</h5>
                <p style="font-size:0.9em; color:var(--text-secondary);">
                    Fill in the content for the defined slots on the left.
                    The preview is available in the main list.
                </p>
            </div>
        </div>
    `,
    setup(props, { emit }) {
        const structure = ref({ header: [], main: [], footer: [] });

        // Parse incoming JSON
        watch(() => props.modelValue, (val) => {
            if (val && typeof val === 'string') {
                try {
                    const parsed = JSON.parse(val);
                    // Only update if different to avoid loop? 
                    // Actually, we trust local reactive state more? 
                    // No, simpler: Just parse initially or if external change.
                    // For now simple parse.
                    if (JSON.stringify(parsed) !== JSON.stringify(structure.value)) {
                        structure.value = parsed;
                    }
                } catch (e) { }
            } else if (typeof val === 'object' && val) {
                structure.value = val;
            }
        }, { immediate: true });

        // Auto-save changes
        watch(structure, (val) => {
            emit('update:modelValue', JSON.stringify(val));
        }, { deep: true });

        // Recursive Finder
        const findSlots = (msg) => {
            const results = [];
            const traverse = (nodes) => {
                if (!nodes || !Array.isArray(nodes)) return;
                nodes.forEach(node => {
                    if (node.slotName) {
                        results.push(node);
                    }
                    if (node.children) {
                        traverse(node.children);
                    }
                });
            };

            traverse(structure.value.header);
            traverse(structure.value.main);
            traverse(structure.value.footer);
            return results;
        };

        const slots = computed(() => findSlots());

        return {
            slots
        };
    }
};
