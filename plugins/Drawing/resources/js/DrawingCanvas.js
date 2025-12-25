import { ref, onMounted, computed, watch, onUnmounted } from 'vue';
import Icon from 'ui/Icon.js';
import ConfirmModal from 'ui/ConfirmModal.js';
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
        overflow-y: auto;
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
    components: { LucideIcon: Icon, ConfirmModal },
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
                    @dblclick="finishPath"
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

        <div v-if="showListModal" class="modal-overlay">
            <div class="modal" style="height: 500px; display: flex; flex-direction: column;">
                <div class="modal-header"><h3>Load Drawing</h3></div>
                <div class="modal-body" style="flex: 1; overflow: auto;">
                    <div v-if="savedArtworks.length === 0" class="p-4 text-center text-muted">
                        No saved drawings found.
                    </div>
                     <div v-else class="flex flex-col gap-2">
                        <div v-for="art in savedArtworks" :key="art.id" class="p-3 bg-white/5 rounded border border-gray-700 flex justify-between items-center group">
                            <div>
                                <div class="font-bold">{{ art.title }}</div>
                                <div class="text-xs text-muted">{{ new Date(art.updated_at).toLocaleDateString() }}</div>
                            </div>
                            <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button class="btn btn-sm btn-primary" @click="loadArtwork(art.id)">Load</button>
                                <button class="btn btn-sm btn-danger icon-only" @click="deleteArtwork(art.id)"><LucideIcon name="trash" size="14" /></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="showListModal = false">Close</button>
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="showClearConfirm"
            title="Clear Canvas"
            message="Are you sure you want to clear the canvas? This action cannot be undone."
            confirm-text="Clear"
            @cancel="showClearConfirm = false"
            @confirm="performClear"
        />
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
        const savedArtworks = ref([]);
        const showSaveModal = ref(false);
        const showListModal = ref(false);
        const showClearConfirm = ref(false);

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

        const startX = ref(0);
        const startY = ref(0);
        const savedImageData = ref(null);
        const pathPoints = ref([]); // Array of {x, y}

        const startDrawing = (e) => {
            const ctx = canvasRef.value.getContext('2d', { willReadFrequently: true });
            const rect = canvasRef.value.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            if (currentTool.value === 'path') {
                if (!isDrawing.value) {
                    // Start new path
                    isDrawing.value = true;
                    // Save background ONLY at the very start of the path
                    savedImageData.value = ctx.getImageData(0, 0, canvasRef.value.width, canvasRef.value.height);
                    pathPoints.value = [{ x, y }];
                } else {
                    // Add point to existing path
                    pathPoints.value.push({ x, y });
                }
                return;
            }

            isDrawing.value = true;
            startX.value = x;
            startY.value = y;

            // Save canvas state for shapes
            if (['rect', 'circle', 'line'].includes(currentTool.value)) {
                savedImageData.value = ctx.getImageData(0, 0, canvasRef.value.width, canvasRef.value.height);
            }

            ctx.beginPath();
            ctx.moveTo(startX.value, startY.value);
            ctx.strokeStyle = currentTool.value === 'eraser' ? '#ffffff' : strokeColor.value;
            ctx.lineWidth = brushSize.value;
            ctx.globalAlpha = brushOpacity.value / 100;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
        };

        const draw = (e) => {
            if (!isDrawing.value) return;
            const ctx = canvasRef.value.getContext('2d', { willReadFrequently: true });
            const rect = canvasRef.value.getBoundingClientRect();
            const currentX = e.clientX - rect.left;
            const currentY = e.clientY - rect.top;

            if (currentTool.value === 'pencil' || currentTool.value === 'eraser') {
                ctx.lineTo(currentX, currentY);
                ctx.stroke();
            } else if (currentTool.value === 'path') {
                if (!savedImageData.value || pathPoints.value.length === 0) return;

                // Restore background
                ctx.putImageData(savedImageData.value, 0, 0);

                ctx.beginPath();
                ctx.strokeStyle = strokeColor.value;
                ctx.lineWidth = brushSize.value;
                ctx.globalAlpha = brushOpacity.value / 100;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';

                // Draw established path
                ctx.moveTo(pathPoints.value[0].x, pathPoints.value[0].y);
                for (let i = 1; i < pathPoints.value.length; i++) {
                    ctx.lineTo(pathPoints.value[i].x, pathPoints.value[i].y);
                }

                // Draw rubber band to current mouse pos
                ctx.lineTo(currentX, currentY);
                ctx.stroke();

            } else if (savedImageData.value) {
                // Restore state before drawing new shape frame
                ctx.putImageData(savedImageData.value, 0, 0);

                ctx.beginPath();
                ctx.strokeStyle = strokeColor.value;
                ctx.lineWidth = brushSize.value;
                ctx.globalAlpha = brushOpacity.value / 100;

                if (currentTool.value === 'rect') {
                    ctx.rect(startX.value, startY.value, currentX - startX.value, currentY - startY.value);
                    ctx.stroke();
                } else if (currentTool.value === 'circle') {
                    const radius = Math.sqrt(Math.pow(currentX - startX.value, 2) + Math.pow(currentY - startY.value, 2));
                    ctx.beginPath();
                    ctx.arc(startX.value, startY.value, radius, 0, 2 * Math.PI);
                    ctx.stroke();
                }
            }
        };

        const stopDrawing = () => {
            if (!isDrawing.value) return;

            // For path tool, we DON'T stop drawing on mouseup. 
            // We only stop on dblclick or Escape/Enter.
            if (currentTool.value === 'path') return;

            isDrawing.value = false;
            savedImageData.value = null;
            const ctx = canvasRef.value.getContext('2d', { willReadFrequently: true });
            ctx.beginPath(); // Close path
        };

        const finishPath = () => {
            if (currentTool.value !== 'path' || !isDrawing.value) return;

            // Logic to finalize path is mostly implicit because we've been drawing on the canvas.
            // But we might want to ensure the last segment is drawn cleanly if double click happens.
            // Actually, the double click itself might fire a mousedown/up sequence.
            // We just need to reset state so the "rubber band" stops.

            // However, because we restore `savedImageData` on every frame, we need to make sure
            // the FINAL render includes the lines we want, WITHOUT the rubber band to cursor.

            const ctx = canvasRef.value.getContext('2d', { willReadFrequently: true });
            if (savedImageData.value) {
                ctx.putImageData(savedImageData.value, 0, 0); // Restore back to BEFORE path started

                // Redraw the FULL path
                if (pathPoints.value.length > 1) {
                    ctx.beginPath();
                    ctx.strokeStyle = strokeColor.value;
                    ctx.lineWidth = brushSize.value;
                    ctx.globalAlpha = brushOpacity.value / 100;
                    ctx.lineCap = 'round';
                    ctx.lineJoin = 'round';

                    ctx.moveTo(pathPoints.value[0].x, pathPoints.value[0].y);
                    for (let i = 1; i < pathPoints.value.length; i++) {
                        ctx.lineTo(pathPoints.value[i].x, pathPoints.value[i].y);
                    }
                    ctx.stroke();
                }
            }

            isDrawing.value = false;
            savedImageData.value = null;
            pathPoints.value = [];
        };

        const cancelPath = () => {
            if (currentTool.value !== 'path' || !isDrawing.value) return;

            // Restore original state (wiping out the path in progress)
            if (savedImageData.value) {
                const ctx = canvasRef.value.getContext('2d', { willReadFrequently: true });
                ctx.putImageData(savedImageData.value, 0, 0);
            }

            isDrawing.value = false;
            savedImageData.value = null;
            pathPoints.value = [];
        };

        const clearCanvas = () => {
            showClearConfirm.value = true;
        };

        const performClear = () => {
            const ctx = canvasRef.value.getContext('2d', { willReadFrequently: true });
            ctx.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height);
            showClearConfirm.value = false;
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
                // Refresh list if open or just invalidate
                fetchArtworks();
            }
        };

        const fetchArtworks = async () => {
            try {
                const res = await fetch('/@/drawing/artworks');
                if (res.ok) {
                    savedArtworks.value = await res.json();
                }
            } catch (e) {
                console.error('Failed to fetch artworks', e);
            }
        };

        const loadArtwork = async (id) => {
            try {
                const res = await fetch(`/@/drawing/artworks/${id}`);
                if (res.ok) {
                    const artwork = await res.json();

                    // Update State
                    currentArtwork.value = { id: artwork.id, title: artwork.title };
                    skillLevel.value = artwork.level || 'beginner';
                    backgroundImage.value = artwork.background_image;

                    // Restore Canvas
                    const img = new Image();
                    img.onload = () => {
                        const ctx = canvasRef.value.getContext('2d', { willReadFrequently: true });
                        ctx.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height);
                        ctx.drawImage(img, 0, 0);
                        // Save this state as base for shapes
                        savedImageData.value = null;
                    };
                    img.src = artwork.content;

                    showListModal.value = false;
                    store.addNotification('Drawing loaded!', 'success');
                }
            } catch (e) {
                console.error('Failed to load artwork', e);
                store.addNotification('Failed to load drawing', 'error');
            }
        };

        const deleteArtwork = async (id) => {
            if (!confirm('Are you sure you want to delete this drawing?')) return;
            try {
                const res = await fetch(`/@/drawing/artworks/${id}`, { method: 'DELETE' });
                if (res.ok) {
                    savedArtworks.value = savedArtworks.value.filter(a => a.id !== id);
                    if (currentArtwork.value.id === id) {
                        currentArtwork.value.id = null; // Detach current if deleted
                    }
                    store.addNotification('Drawing deleted', 'success');
                }
            } catch (e) {
                store.addNotification('Failed to delete drawing', 'error');
            }
        };

        watch(showListModal, (newVal) => {
            if (newVal) {
                fetchArtworks();
            }
        });

        onMounted(() => {
            const styleId = 'drawing-plugin-styles';
            if (!document.getElementById(styleId)) {
                const style = document.createElement('style');
                style.id = styleId;
                style.textContent = STYLES;
                document.head.appendChild(style);
            }

            window.addEventListener('keydown', handleKeydown);
        });

        onUnmounted(() => {
            window.removeEventListener('keydown', handleKeydown);
        });

        const handleKeydown = (e) => {
            if (currentTool.value === 'path' && isDrawing.value) {
                if (e.key === 'Enter') {
                    finishPath();
                } else if (e.key === 'Escape') {
                    cancelPath();
                }
            }
        };

        return {
            canvasRef, skillLevel, currentTool, strokeColor, brushSize, brushOpacity,
            backgroundImage, isDrawing, currentArtwork, showSaveModal, showListModal,
            palette, availableTools, startDrawing, draw, stopDrawing, clearCanvas,
            selectBackgroundImage, confirmSave, showClearConfirm, performClear,
            savedArtworks, loadArtwork, deleteArtwork, fetchArtworks, finishPath
        };
    }
}
