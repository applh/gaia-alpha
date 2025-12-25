import { ref, onMounted, computed, watch } from 'vue';
import Icon from 'ui/Icon.js';
import UIButton from 'ui/Button.js';
import Card from 'ui/Card.js';
import Container from 'ui/Container.js';
import Row from 'ui/Row.js';
import Col from 'ui/Col.js';
import Modal from 'ui/Modal.js';
import Input from 'ui/Input.js';
import { UITitle, UIText } from 'ui/Typography.js';
import Divider from 'ui/Divider.js';
import { store } from 'store';
import SlidesPlayer from './components/SlidesPlayer.js';
import CodeEditor from 'ui/CodeEditor.js';
import { marked } from 'marked';

const STYLES = `
    .slides-body {
        flex: 1;
        display: flex;
        overflow: hidden;
        height: calc(100vh - 180px);
        min-height: 500px;
    }
    .page-list-item {
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
        margin-bottom: 12px;
    }
    .page-list-item:hover { border-color: var(--border-color); }
    .page-list-item.active { border-color: var(--accent-color); box-shadow: 0 0 10px rgba(99, 102, 241, 0.3); }
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
        background: rgba(0,0,0,0.1);
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
        box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        position: relative;
        border-radius: 8px;
        overflow: hidden;
    }
    .markdown-preview {
        background: white;
        padding: 2rem;
        height: 100%;
        overflow-y: auto;
        border-radius: 4px;
        border: 1px solid var(--border-color);
    }
    .markdown-preview h1 { font-size: 2.5em; margin-bottom: 0.5em; }
    .markdown-preview h2 { font-size: 2em; margin-bottom: 0.5em; }
    .markdown-preview h3 { font-size: 1.5em; margin-bottom: 0.5em; }
    .markdown-preview p { margin-bottom: 1em; line-height: 1.6; }
    .markdown-preview ul, .markdown-preview ol { margin-left: 1.5em; margin-bottom: 1em; }
    .markdown-preview blockquote { border-left: 4px solid #ddd; padding-left: 1em; color: #666; }
    .markdown-preview code { background: #f5f5f5; padding: 0.2rem 0.4rem; border-radius: 3px; }
    .markdown-preview pre { background: #f5f5f5; padding: 1rem; border-radius: 4px; overflow-x: auto; }
    .markdown-preview img { max-width: 100%; }
`;

export default {
    components: {
        LucideIcon: Icon,
        'ui-button': UIButton,
        'ui-card': Card,
        'ui-container': Container,
        'ui-row': Row,
        'ui-col': Col,
        'ui-modal': Modal,
        'ui-input': Input,
        'ui-title': UITitle,
        'ui-text': UIText,
        'ui-divider': Divider,
        SlidesPlayer,
        CodeEditor
    },
    template: `
    <ui-container class="slides-container">
        <div class="style-injector" v-html="'<style>' + styles + '</style>'"></div>
        
        <!-- Header -->
        <div class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
            <ui-title :level="1">
                <LucideIcon name="monitor" size="28" style="margin-right: 12px; vertical-align: middle; color: var(--accent-color);" />
                {{ currentDeck ? currentDeck.title : 'Slide Decks' }}
            </ui-title>
            <div style="display: flex; gap: 12px;">
                <template v-if="currentDeck">
                    <ui-button type="primary" @click="saveAll">
                        <LucideIcon name="save" size="18" style="margin-right: 8px;" /> Save
                    </ui-button>
                    <ui-button @click="isPlaying = true">
                        <LucideIcon name="play" size="18" style="margin-right: 8px;" /> Play
                    </ui-button>
                    <ui-button @click="closeEditor">
                        <LucideIcon name="x" size="18" style="margin-right: 8px;" /> Close
                    </ui-button>
                </template>
                <template v-else>
                    <ui-button type="primary" @click="showNewModal = true">
                        <LucideIcon name="plus" size="18" style="margin-right: 8px;" /> New Slide Deck
                    </ui-button>
                </template>
            </div>
        </div>

        <div class="slides-body">
            <!-- List View -->
            <div v-if="!currentDeck" style="width: 100%;">
                <ui-card v-if="decks.length === 0" style="text-align: center; padding: 100px 20px;">
                    <LucideIcon name="monitor" size="64" style="opacity: 0.1; margin-bottom: 24px;" />
                    <ui-title :level="3">No slide decks found</ui-title>
                    <ui-text class="text-muted" style="display: block; margin-bottom: 24px;">Create your first one to start presenting!</ui-text>
                    <ui-button type="primary" @click="showNewModal = true">Create Deck</ui-button>
                </ui-card>
                
                <ui-row v-else :gutter="20">
                    <ui-col v-for="d in decks" :key="d.id" :xs="24" :sm="12" :md="8" :lg="6">
                        <ui-card style="margin-bottom: 20px; transition: transform 0.2s;" @click="openDeck(d)">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                                <LucideIcon name="monitor" size="32" style="color: var(--accent-color);" />
                                <ui-button size="sm" type="danger" @click.stop="deleteDeck(d)">
                                    <LucideIcon name="trash" size="14" />
                                </ui-button>
                            </div>
                            <ui-text weight="bold" style="display: block; font-size: 1.1rem; margin-bottom: 4px;">{{ d.title }}</ui-text>
                            <ui-text size="extra-small" class="text-muted">Updated: {{ formatDate(d.updated_at) }}</ui-text>
                        </ui-card>
                    </ui-col>
                </ui-row>
            </div>

            <!-- Editor View -->
            <template v-else>
                <ui-row :gutter="20" style="width: 100%; height: 100%; margin: 0;">
                    <!-- Sidebar: Pages -->
                    <ui-col :span="6" style="height: 100%;">
                        <ui-card style="height: 100%; display: flex; flex-direction: column; padding: 0;">
                            <div style="padding: 16px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                                <ui-text weight="bold" size="sm" style="text-transform: uppercase; color: var(--text-muted);">Pages</ui-text>
                                <ui-button size="sm" @click="addPage">
                                    <LucideIcon name="plus" size="14" />
                                </ui-button>
                            </div>
                            <div style="flex: 1; overflow-y: auto; padding: 16px;">
                                <div 
                                    v-for="(page, index) in pages" 
                                    :key="page.id"
                                    class="page-list-item"
                                    :class="{ active: currentPageIndex === index }"
                                    @click="selectPage(index)"
                                >
                                    <span class="page-number">{{ index + 1 }}</span>
                                    <LucideIcon v-if="page.slide_type === 'drawing'" name="pen-tool" size="24" style="opacity: 0.3;" />
                                    
                                    <div style="position: absolute; top: 4px; right: 4px; display: flex; gap: 2px;">
                                        <ui-button v-if="index > 0" size="sm" @click.stop="movePage(index, -1)" style="padding: 2px; height: auto;">▲</ui-button>
                                        <ui-button v-if="index < pages.length - 1" size="sm" @click.stop="movePage(index, 1)" style="padding: 2px; height: auto;">▼</ui-button>
                                        <ui-button size="sm" type="danger" @click.stop="deletePage(index)" style="padding: 2px; height: auto;">
                                            <LucideIcon name="trash" size="10" />
                                        </ui-button>
                                    </div>
                                </div>
                            </div>
                        </ui-card>
                    </ui-col>

                    <!-- Main Canvas Area -->
                    <ui-col :span="18" style="height: 100%;">
                        <div class="editor-view" v-if="currentPage">
                            <div style="display: flex; flex-direction: column; gap: 24px; width: 100%; align-items: center;">
                                <div style="display: flex; gap: 12px; width: 100%; max-width: 960px;">
                                    <select v-model="currentPage.slide_type" class="form-input" style="background: var(--card-bg); color: var(--text-color); border: 1px solid var(--border-color); padding: 8px 12px; border-radius: 8px; cursor: pointer;">
                                        <option value="drawing">Drawing Page</option>
                                        <option value="markdown">Markdown Page</option>
                                    </select>
                                </div>
                                
                                <!-- Drawing Mode -->
                                <template v-if="currentPage.slide_type === 'drawing'">
                                    <div class="slide-canvas-wrapper" ref="canvasWrapper">
                                        <canvas 
                                            ref="slideCanvas"
                                            width="1920"
                                            height="1080"
                                            style="width: 100%; height: 100%; touch-action: none;"
                                            @mousedown="startDrawing"
                                            @mousemove="draw"
                                            @mouseup="stopDrawing"
                                            @mouseleave="stopDrawing"
                                        ></canvas>
                                    </div>

                                    <ui-card style="width: 100%; max-width: 960px;">
                                        <div style="display: flex; gap: 16px; align-items: center; justify-content: center;">
                                            <div v-for="c in palette" :key="c" 
                                                style="width: 32px; height: 32px; border-radius: 50%; cursor: pointer; border: 2px solid transparent; transition: transform 0.1s;"
                                                :style="{ background: c, borderColor: strokeColor === c ? 'var(--accent-color)' : 'transparent', transform: strokeColor === c ? 'scale(1.2)' : 'none' }"
                                                @click="strokeColor = c"
                                            ></div>
                                            <ui-divider vertical height="32px" style="margin: 0 8px;" />
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <input type="range" v-model="brushSize" min="1" max="50" style="cursor: pointer;" />
                                                <ui-text size="sm" style="width: 40px;">{{ brushSize }}px</ui-text>
                                            </div>
                                            <ui-divider vertical height="32px" style="margin: 0 8px;" />
                                            <ui-button size="sm" type="danger" @click="clearPage">
                                                <LucideIcon name="trash" size="14" style="margin-right: 4px;" /> Clear
                                            </ui-button>
                                        </div>
                                    </ui-card>
                                </template>

                                <!-- Markdown Mode -->
                                <template v-if="currentPage.slide_type === 'markdown'">
                                    <div style="width: 100%; height: 600px; display: flex; gap: 20px;">
                                        <div style="flex: 1; height: 100%; display: flex; flex-direction: column;">
                                            <ui-text weight="bold" size="sm" style="margin-bottom: 8px;">Editor</ui-text>
                                            <CodeEditor v-model="currentPage.content" mode="markdown" theme="monokai" style="flex: 1;" />
                                        </div>
                                        <div style="flex: 1; height: 100%; display: flex; flex-direction: column;">
                                            <ui-text weight="bold" size="sm" style="margin-bottom: 8px;">Preview</ui-text>
                                            <div class="markdown-preview" v-html="renderMarkdown(currentPage.content)"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </ui-col>
                </ui-row>
            </template>
        </div>

        <!-- Player Overlay -->
        <SlidesPlayer v-if="isPlaying" :slides="pages" :onClose="() => isPlaying = false" />

        <!-- New Deck Modal -->
        <ui-modal :show="showNewModal" title="New Slide Deck" @close="showNewModal = false">
            <ui-input v-model="newTitle" label="Deck Title" placeholder="Enter deck title..." @keyup.enter="createDeck" />
            <template #footer>
                <ui-button @click="showNewModal = false">Cancel</ui-button>
                <ui-button type="primary" @click="createDeck">Create Deck</ui-button>
            </template>
        </ui-modal>
        
        <!-- Delete Confirmation Modal -->
        <ui-modal :show="showDeleteModal" title="Delete Slide Deck" @close="showDeleteModal = false">
            <ui-text>Are you sure you want to delete deck "<strong>{{ deckToDelete?.title }}</strong>"?</ui-text>
            <ui-text size="sm" class="text-muted" style="display: block; margin-top: 12px;">This action cannot be undone and will remove all pages.</ui-text>
            <template #footer>
                <ui-button @click="showDeleteModal = false">Cancel</ui-button>
                <ui-button type="danger" @click="confirmDeleteDeck">Delete Deck</ui-button>
            </template>
        </ui-modal>
    </ui-container>
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
                const d = decks.value.find(d => d.id === Number(result.id));
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

        const renderMarkdown = (text) => {
            if (!text) return '';
            if (typeof text !== 'string') return '';
            if (text.startsWith('data:image')) return '<p><em>(Image content hidden)</em></p>';
            try {
                return marked.parse(text);
            } catch (e) {
                console.error(e);
                return '<p class="text-error">Error rendering markdown</p>';
            }
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
            deleteDeck, confirmDeleteDeck, showDeleteModal, deckToDelete,
            renderMarkdown,
            styles: STYLES
        };
    }
}
