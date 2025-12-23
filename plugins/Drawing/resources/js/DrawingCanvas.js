import { ref, onMounted, computed, watch } from 'vue';
import Icon from 'ui/Icon.js';
import { store } from 'store';

const STYLES = `
    .drawing-container {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 100px);
        background: var(--bg-color);
        color: var(--text-primary);
    }
    .drawing-header {
        padding: 1rem;
        background: var(--card-bg);
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .drawing-body {
        flex: 1;
        display: flex;
        overflow: hidden;
    }
    .drawing-sidebar {
        width: 250px;
        background: var(--card-bg);
        border-right: 1px solid var(--border-color);
        padding: 1rem;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    .canvas-wrapper {
        flex: 1;
        position: relative;
        background: #222;
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: auto;
    }
    canvas {
        background: white;
        box-shadow: 0 0 20px rgba(0,0,0,0.5);
        cursor: crosshair;
    }
    .bg-layer {
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        object-fit: contain;
        pointer-events: none;
        opacity: 0.3;
    }
    .tool-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .tool-btn {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        background: var(--glass-bg);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
        text-align: left;
    }
    .tool-btn:hover { background: var(--border-color); }
    .tool-btn.active {
        background: var(--accent-color);
        color: white;
        border-color: var(--accent-color);
    }
    .skill-level-selector {
        width: 100%;
        padding: 0.5rem;
        border-radius: 6px;
        background: var(--input-bg);
        color: var(--text-primary);
        border: 1px solid var(--border-color);
    }
    .color-swatch {
        width: 24px;
        height: 24px;
        border-radius: 4px;
        cursor: pointer;
        border: 2px solid transparent;
    }
    .color-swatch.active { border-color: white; outline: 1px solid black; }
    .palette {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.5rem;
    }
`;

export default {
    components: { LucideIcon: Icon },
    template: `
    <div class="drawing-container">
        <div class="drawing-header">
            <div class="flex items-center gap-4">
                <LucideIcon name="pen-tool" size="24" />
                <h2 class="text-xl font-bold">{{ currentArtwork.title || 'New Drawing' }}</h2>
                <select v-model="skillLevel" class="skill-level-selector ml-4" style="width: 120px;">
                    <option value="beginner">Beginner</option>
                    <option value="pro">Pro</option>
                    <option value="expert">Expert</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button @click="showSaveModal = true" class="btn btn-primary">Save</button>
                <button @click="showListModal = true" class="btn btn-secondary">Load</button>
                <button @click="clearCanvas" class="btn btn-danger">Clear</button>
            </div>
        </div>

        <div class="drawing-body">
            <div class="drawing-sidebar">
                <div class="tool-group">
                    <label class="text-xs font-bold uppercase text-muted">Tools</label>
                    <button 
                        v-for="tool in availableTools" 
                        :key="tool.id"
                        @click="currentTool = tool.id"
                        :class="['tool-btn', { active: currentTool === tool.id }]"
                    >
                        <LucideIcon :name="tool.icon" size="18" />
                        <span>{{ tool.name }}</span>
                    </button>
                </div>

                <div class="tool-group" v-if="skillLevel !== 'beginner'">
                    <label class="text-xs font-bold uppercase text-muted">Settings</label>
                    <div class="flex flex-col gap-2">
                        <span class="text-xs">Size: {{ brushSize }}px</span>
                        <input type="range" v-model="brushSize" min="1" max="50" class="w-full">
                        <span class="text-xs">Opacity: {{ brushOpacity }}%</span>
                        <input type="range" v-model="brushOpacity" min="1" max="100" class="w-full">
                    </div>
                </div>

                <div class="tool-group">
                    <label class="text-xs font-bold uppercase text-muted">Colors</label>
                    <div class="palette">
                        <div 
                            v-for="c in palette" 
                            :key="c"
                            class="color-swatch"
                            :style="{ background: c }"
                            :class="{ active: strokeColor === c }"
                            @click="strokeColor = c"
                        ></div>
                    </div>
                    <input v-if="skillLevel !== 'beginner'" type="color" v-model="strokeColor" class="w-full mt-2 h-8">
                </div>

                <div class="tool-group" v-if="skillLevel !== 'beginner'">
                    <label class="text-xs font-bold uppercase text-muted">Background</label>
                    <button @click="selectBackgroundImage" class="btn btn-secondary btn-sm">
                        <LucideIcon name="image" size="14" class="mr-2" />
                        Select Image
                    </button>
                    <div v-if="backgroundImage" class="mt-2 flex items-center justify-between">
                        <span class="text-xs truncate max-w-[150px]">Image Loaded</span>
                        <LucideIcon name="trash" size="14" class="text-danger cursor-pointer" @click="backgroundImage = null" />
                    </div>
                </div>
            </div>

            <div class="canvas-wrapper" ref="wrapperRef">
                <img v-if="backgroundImage" :src="backgroundImage" class="bg-layer" />
                <canvas 
                    ref="canvasRef" 
                    width="800" 
                    height="600"
                    @mousedown="startDrawing"
                    @mousemove="draw"
                    @mouseup="stopDrawing"
                    @mouseleave="stopDrawing"
                ></canvas>
            </div>
        </div>

        <!-- Modals for Save/Load -->
        <div v-if="showSaveModal" class="modal-overlay">
            <div class="modal">
                <div class="modal-header"><h3>Save Drawing</h3></div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Title</label>
                        <input v-model="currentArtwork.title" class="form-input" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="showSaveModal = false">Cancel</button>
                    <button class="btn btn-primary" @click="confirmSave">Save</button>
                </div>
            </div>
        </div>
    </div>
    `,
    styles: STYLES,
    setup() {
        const canvasRef = ref(null);
        const skillLevel = ref('beginner');
        const currentTool = ref('pencil');
        const strokeColor = ref('#000000');
        const brushSize = ref(5);
        const brushOpacity = ref(100);
        const backgroundImage = ref(null);
        const isDrawing = ref(false);
        const currentArtwork = ref({ id: null, title: 'Untitled' });
        const showSaveModal = ref(false);
        const showListModal = ref(false);

        const palette = ['#000000', '#ffffff', '#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff', '#00ffff'];

        const availableTools = computed(() => {
            const tools = [{ id: 'pencil', name: 'Pencil', icon: 'pencil' }, { id: 'eraser', name: 'Eraser', icon: 'eraser' }];
            if (skillLevel.value !== 'beginner') {
                tools.push({ id: 'rect', name: 'Rectangle', icon: 'square' });
                tools.push({ id: 'circle', name: 'Circle', icon: 'circle' });
            }
            if (skillLevel.value === 'expert') {
                tools.push({ id: 'path', name: 'Path', icon: 'git-branch' });
            }
            return tools;
        });

        const startDrawing = (e) => {
            isDrawing.value = true;
            const ctx = canvasRef.value.getContext('2d');
            const rect = canvasRef.value.getBoundingClientRect();
            ctx.beginPath();
            ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
            ctx.strokeStyle = currentTool.value === 'eraser' ? '#ffffff' : strokeColor.value;
            ctx.lineWidth = brushSize.value;
            ctx.globalAlpha = brushOpacity.value / 100;
            ctx.lineCap = 'round';
        };

        const draw = (e) => {
            if (!isDrawing.value) return;
            const ctx = canvasRef.value.getContext('2d');
            const rect = canvasRef.value.getBoundingClientRect();
            ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
            ctx.stroke();
        };

        const stopDrawing = () => {
            if (!isDrawing.value) return;
            isDrawing.value = false;
        };

        const clearCanvas = () => {
            if (!confirm('Clear canvas?')) return;
            const ctx = canvasRef.value.getContext('2d');
            ctx.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height);
        };

        const selectBackgroundImage = () => {
            // In a real implementation, this would open the MediaLibrary
            // For now, we simulate with a prompt or lucky choice
            const url = prompt('Enter background image URL:', 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=800&q=80');
            if (url) backgroundImage.ref = url; // simulated
            backgroundImage.value = url;
        };

        const confirmSave = async () => {
            const data = {
                id: currentArtwork.value.id,
                title: currentArtwork.value.title,
                level: skillLevel.value,
                background_image: backgroundImage.value,
                content: canvasRef.value.toDataURL()
            };
            const res = await fetch('/@/drawing/artworks/save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            if (res.ok) {
                const result = await res.json();
                currentArtwork.value.id = result.id;
                showSaveModal.value = false;
                store.addNotification('Drawing saved!', 'success');
            }
        };

        onMounted(() => {
            const styleId = 'drawing-plugin-styles';
            if (!document.getElementById(styleId)) {
                const style = document.createElement('style');
                style.id = styleId;
                style.textContent = STYLES;
                document.head.appendChild(style);
            }
        });

        return {
            canvasRef, skillLevel, currentTool, strokeColor, brushSize, brushOpacity,
            backgroundImage, isDrawing, currentArtwork, showSaveModal, showListModal,
            palette, availableTools, startDrawing, draw, stopDrawing, clearCanvas,
            selectBackgroundImage, confirmSave
        };
    }
}
