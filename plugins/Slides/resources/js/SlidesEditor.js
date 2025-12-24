import { ref, onMounted, computed, watch } from 'vue';
import Icon from 'ui/Icon.js';
import { store } from 'store';
import SlidesPlayer from './components/SlidesPlayer.js';

const STYLES = `
    .slides-container {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 100px);
        background: var(--bg-color);
        color: var(--text-primary);
    }
    .slides-header {
        padding: 1rem;
        background: var(--card-bg);
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .slides-body {
        flex: 1;
        display: flex;
        overflow: hidden;
    }
    .slides-sidebar {
        width: 280px;
        background: var(--card-bg);
        border-right: 1px solid var(--border-color);
        display: flex;
        flex-direction: column;
    }
    .page-list {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .page-item {
        aspect-ratio: 16/9;
        background: #333;
        border: 2px solid transparent;
        border-radius: 8px;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: all 0.2s;
    }
    .page-item:hover { border-color: var(--border-color); }
    .page-item.active { border-color: var(--accent-color); box-shadow: 0 0 10px var(--accent-color); }
    .page-number {
        position: absolute;
        top: 4px; left: 8px;
        font-size: 10px;
        font-weight: bold;
        color: white;
        background: rgba(0,0,0,0.5);
        padding: 2px 4px;
        border-radius: 4px;
    }
    .editor-view {
        flex: 1;
        background: #222;
        padding: 2rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        overflow-y: auto;
    }
    .slide-canvas-wrapper {
        width: 100%;
        max-width: 960px;
        aspect-ratio: 16/9;
        background: white;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        position: relative;
    }
    .deck-list {
        padding: 2rem;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1.5rem;
    }
    .deck-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1.5rem;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .deck-card:hover { 
        transform: translateY(-5px); 
        border-color: var(--accent-color);
        background: var(--glass-bg);
    }
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        gap: 1rem;
        color: var(--text-muted);
    }
    .btn-icon {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
`;

export default {
    components: { LucideIcon: Icon, SlidesPlayer },
    template: `
    <div class="slides-container">
        <!-- Header -->
        <div class="slides-header">
            <div class="flex items-center gap-4">
                <LucideIcon name="monitor" size="24" />
                <h2 v-if="currentDeck" class="text-xl font-bold">{{ currentDeck.title }}</h2>
                <h2 v-else class="text-xl font-bold">Slides</h2>
            </div>
            <div class="flex gap-2">
                <template v-if="currentDeck">
                    <button @click="saveAll" class="btn btn-primary btn-icon">
                        <LucideIcon name="save" size="18" /> Save
                    </button>
                    <button @click="isPlaying = true" class="btn btn-secondary btn-icon">
                        <LucideIcon name="play" size="18" /> Play
                    </button>
                    <button @click="closeEditor" class="btn btn-ghost btn-icon">
                        <LucideIcon name="x" size="18" /> Close
                    </button>
                </template>
                <template v-else>
                    <button @click="showNewModal = true" class="btn btn-primary btn-icon">
                        <LucideIcon name="plus" size="18" /> New Slide Deck
                    </button>
                </template>
            </div>
        </div>

        <div class="slides-body">
            <!-- List View -->
            <div v-if="!currentDeck" class="w-full">
                <div v-if="decks.length === 0" class="empty-state">
                    <LucideIcon name="monitor" size="64" />
                    <p>No slide decks found. Create your first one!</p>
                    <button @click="showNewModal = true" class="btn btn-primary">Create Deck</button>
                </div>
                <div v-else class="deck-list">
                    <div 
                        v-for="d in decks" 
                        :key="d.id" 
                        class="deck-card"
                        @click="openDeck(d)"
                    >
                        <div class="flex justify-between items-start w-full">
                            <LucideIcon name="monitor" size="32" class="text-accent" />
                            <button @click.stop="deleteDeck(d)" class="text-danger hover:text-red-400 p-1">
                                <LucideIcon name="trash" size="16" />
                            </button>
                        </div>
                        <h3 class="font-bold">{{ d.title }}</h3>
                        <span class="text-xs text-muted">{{ formatDate(d.updated_at) }}</span>
                    </div>
                </div>
            </div>

            <!-- Editor View -->
            <template v-else>
                <div class="slides-sidebar">
                    <div class="p-4 border-bottom flex justify-between items-center bg-muted">
                        <label class="text-xs font-bold uppercase text-muted">Pages</label>
                        <button @click="addPage" class="btn btn-secondary btn-xs">
                            <LucideIcon name="plus" size="14" />
                        </button>
                    </div>
                    <div class="page-list">
                        <div 
                            v-for="(page, index) in pages" 
                            :key="page.id"
                            :class="['page-item', { active: currentPageIndex === index }]"
                            @click="selectPage(index)"
                        >
                            <span class="page-number">{{ index + 1 }}</span>
                            <LucideIcon v-if="page.slide_type === 'drawing'" name="pen-tool" size="24" class="text-muted" />
                            <div v-if="index > 0" @click.stop="movePage(index, -1)" class="absolute top-0 right-8 p-1 text-xs hover:text-accent">▲</div>
                            <div v-if="index < pages.length - 1" @click.stop="movePage(index, 1)" class="absolute top-0 right-4 p-1 text-xs hover:text-accent">▼</div>
                            <button @click.stop="deletePage(index)" class="absolute bottom-1 right-1 text-danger hover:text-red-400">
                                <LucideIcon name="trash" size="14" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="editor-view" v-if="currentPage">
                    <div class="flex flex-col gap-4 w-full items-center">
                        <div class="flex gap-4 items-center w-full max-w-[960px]">
                            <select v-model="currentPage.slide_type" class="form-input" style="width: auto;">
                                <option value="drawing">Drawing Page</option>
                                <option value="markdown">Markdown Page (Coming Soon)</option>
                            </select>
                        </div>
                        <div class="slide-canvas-wrapper" ref="canvasWrapper">
                            <canvas 
                                ref="slideCanvas"
                                width="1920"
                                height="1080"
                                class="w-full h-full"
                                @mousedown="startDrawing"
                                @mousemove="draw"
                                @mouseup="stopDrawing"
                                @mouseleave="stopDrawing"
                            ></canvas>
                        </div>
                        <div class="flex gap-4 items-center bg-card p-4 rounded-xl border w-full max-w-[960px] justify-center">
                            <div v-for="c in palette" :key="c" 
                                class="w-8 h-8 rounded-full cursor-pointer border-2"
                                :style="{ background: c, borderColor: strokeColor === c ? 'var(--accent-color)' : 'transparent' }"
                                @click="strokeColor = c"
                            ></div>
                            <input type="range" v-model="brushSize" min="1" max="50" class="ml-4">
                            <span class="text-xs w-8">{{ brushSize }}px</span>
                            <button @click="clearPage" class="btn btn-ghost btn-sm text-danger">Clear</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Player Overlay -->
        <SlidesPlayer v-if="isPlaying" :slides="pages" :onClose="() => isPlaying = false" />

        <!-- New Deck Modal -->
        <div v-if="showNewModal" class="modal-overlay">
            <div class="modal">
                <div class="modal-header"><h3>New Slide Deck</h3></div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Title</label>
                        <input v-model="newTitle" class="form-input" placeholder="Enter deck title..." @keyup.enter="createDeck" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="showNewModal = false">Cancel</button>
                    <button class="btn btn-primary" @click="createDeck">Create</button>
                </div>
            </div>
        </div>
        
        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="modal-overlay">
            <div class="modal">
                <div class="modal-header"><h3>Delete Deck</h3></div>
                <div class="modal-body">
                    <p>Are you sure you want to delete deck "<strong>{{ deckToDelete?.title }}</strong>"?</p>
                    <p class="text-sm text-muted mt-2">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="showDeleteModal = false">Cancel</button>
                    <button class="btn btn-danger" @click="confirmDeleteDeck">Delete</button>
                </div>
            </div>
        </div>
    </div>
    `,
    styles: STYLES,
    setup() {
        const decks = ref([]);
        const currentDeck = ref(null);
        const pages = ref([]);
        const currentPageIndex = ref(0);
        const showNewModal = ref(false);
        const newTitle = ref('');
        const isPlaying = ref(false);
        const showDeleteModal = ref(false);
        const deckToDelete = ref(null);

        // Drawing state
        const slideCanvas = ref(null);
        const isDrawing = ref(false);
        const strokeColor = ref('#000000');
        const brushSize = ref(5);
        const palette = ['#000000', '#ffffff', '#ff0000', '#22c55e', '#3b82f6', '#eab308', '#a855f7', '#ec4899'];

        const currentPage = computed(() => pages.value[currentPageIndex.value]);

        const loadDecks = async () => {
            const res = await fetch('/@/slides/list');
            decks.value = await res.json();
        };

        const createDeck = async () => {
            if (!newTitle.value) return;
            const res = await fetch('/@/slides/deck/save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title: newTitle.value })
            });
            if (res.ok) {
                const result = await res.json();
                showNewModal.value = false;
                newTitle.value = '';
                await loadDecks();
                const d = decks.value.find(d => d.id === result.id);
                if (d) openDeck(d);
            }
        };

        const openDeck = async (d) => {
            currentDeck.value = d;
            const res = await fetch(`/@/slides/deck/${d.id}/pages`);
            pages.value = await res.json();
            if (pages.value.length === 0) {
                await addPage();
            } else {
                currentPageIndex.value = 0;
                renderCurrentPage();
            }
        };

        const closeEditor = () => {
            currentDeck.value = null;
            pages.value = [];
            loadDecks();
        };

        const addPage = async () => {
            const res = await fetch(`/@/slides/deck/${currentDeck.value.id}/pages/add`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ content: '[]', slide_type: 'drawing' })
            });
            if (res.ok) {
                const resPages = await fetch(`/@/slides/deck/${currentDeck.value.id}/pages`);
                pages.value = await resPages.json();
                currentPageIndex.value = pages.value.length - 1;
                renderCurrentPage();
            }
        };

        const selectPage = (index) => {
            if (slideCanvas.value) {
                currentPage.value.content = slideCanvas.value.toDataURL();
            }
            currentPageIndex.value = index;
        };

        const renderCurrentPage = () => {
            setTimeout(() => {
                if (!slideCanvas.value || !currentPage.value) return;
                const ctx = slideCanvas.value.getContext('2d');
                ctx.clearRect(0, 0, slideCanvas.value.width, slideCanvas.value.height);

                if (currentPage.value.content && currentPage.value.content !== '[]') {
                    const img = new Image();
                    img.onload = () => {
                        ctx.drawImage(img, 0, 0);
                    };
                    img.src = currentPage.value.content;
                }
            }, 50);
        };

        const startDrawing = (e) => {
            isDrawing.value = true;
            const ctx = slideCanvas.value.getContext('2d');
            const rect = slideCanvas.value.getBoundingClientRect();
            const scaleX = slideCanvas.value.width / rect.width;
            const scaleY = slideCanvas.value.height / rect.height;

            ctx.beginPath();
            ctx.moveTo((e.clientX - rect.left) * scaleX, (e.clientY - rect.top) * scaleY);
            ctx.strokeStyle = strokeColor.value;
            ctx.lineWidth = brushSize.value * scaleX;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
        };

        const draw = (e) => {
            if (!isDrawing.value) return;
            const ctx = slideCanvas.value.getContext('2d');
            const rect = slideCanvas.value.getBoundingClientRect();
            const scaleX = slideCanvas.value.width / rect.width;
            const scaleY = slideCanvas.value.height / rect.height;

            ctx.lineTo((e.clientX - rect.left) * scaleX, (e.clientY - rect.top) * scaleY);
            ctx.stroke();
        };

        const stopDrawing = () => {
            if (!isDrawing.value) return;
            isDrawing.value = false;
            currentPage.value.content = slideCanvas.value.toDataURL();
        };

        const clearPage = () => {
            const ctx = slideCanvas.value.getContext('2d');
            ctx.clearRect(0, 0, slideCanvas.value.width, slideCanvas.value.height);
            currentPage.value.content = '[]';
        };

        const saveAll = async () => {
            if (slideCanvas.value) {
                currentPage.value.content = slideCanvas.value.toDataURL();
            }

            for (const page of pages.value) {
                await fetch(`/@/slides/pages/${page.id}/update`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        content: page.content,
                        slide_type: page.slide_type
                    })
                });
            }

            await fetch(`/@/slides/deck/${currentDeck.value.id}/pages/reorder`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ page_ids: pages.value.map(p => p.id) })
            });

            store.addNotification('Slide deck saved!', 'success');
        };

        const movePage = (index, direction) => {
            if (index + direction < 0 || index + direction >= pages.value.length) return;
            const temp = pages.value[index];
            pages.value[index] = pages.value[index + direction];
            pages.value[index + direction] = temp;
            if (currentPageIndex.value === index) currentPageIndex.value += direction;
            else if (currentPageIndex.value === index + direction) currentPageIndex.value -= direction;
        };

        const deletePage = async (index) => {
            if (!confirm('Delete this page?')) return;
            const res = await fetch(`/@/slides/pages/${pages.value[index].id}`, { method: 'DELETE' });
            if (res.ok) {
                pages.value.splice(index, 1);
                if (currentPageIndex.value >= pages.value.length) {
                    currentPageIndex.value = Math.max(0, pages.value.length - 1);
                }
                renderCurrentPage();
            }
        };

        const formatDate = (dateStr) => {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        };

        const deleteDeck = (deck) => {
            deckToDelete.value = deck;
            showDeleteModal.value = true;
        };

        const confirmDeleteDeck = async () => {
            if (!deckToDelete.value) return;

            const res = await fetch(`/@/slides/deck/${deckToDelete.value.id}`, { method: 'DELETE' });
            if (res.ok) {
                await loadDecks();
                store.addNotification('Slide deck deleted', 'success');
            }
            showDeleteModal.value = false;
            deckToDelete.value = null;
        };

        onMounted(async () => {
            await loadDecks();

            const styleId = 'slides-plugin-styles';
            if (!document.getElementById(styleId)) {
                const style = document.createElement('style');
                style.id = styleId;
                style.textContent = STYLES;
                document.head.appendChild(style);
            }
        });

        watch(currentPageIndex, () => {
            renderCurrentPage();
        });

        return {
            decks, currentDeck, pages, currentPageIndex,
            showNewModal, newTitle, slideCanvas, currentPage,
            strokeColor, brushSize, palette, isPlaying,
            loadDecks, createDeck, openDeck, addPage,
            startDrawing, draw, stopDrawing, clearPage, saveAll,
            movePage, deletePage, formatDate, closeEditor, selectPage,
            deleteDeck, confirmDeleteDeck, showDeleteModal, deckToDelete
        };
    }
}
