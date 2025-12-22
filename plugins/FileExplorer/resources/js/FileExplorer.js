import { ref, onMounted, computed, watch } from 'vue';
import Icon from 'ui/Icon.js';
import TreeView from './components/TreeView.js';
import FileEditor from './components/FileEditor.js';
import ImageEditor from 'ui/ImageEditor.js';
import VideoPlayer from 'ui/VideoPlayer.js';
import VideoEditor from 'ui/VideoEditor.js';
import JsonEditor from './components/JsonEditor.js';

export default {
    components: {
        LucideIcon: Icon,
        TreeView,
        FileEditor,
        ImageEditor,
        VideoPlayer,
        VideoEditor,
        JsonEditor
    },
    template: `
    <div class="file-explorer-container admin-page">
        <div class="admin-header">
            <h2 class="page-title">
                <LucideIcon name="folder-tree" size="32" />
                File Explorer
            </h2>
            <div class="explorer-toolbar">
                <div class="btn-group">
                    <button @click="mode = 'real'" :class="['btn btn-sm', mode === 'real' ? 'btn-primary' : 'btn-outline']">Real FS</button>
                    <button @click="mode = 'vfs'" :class="['btn btn-sm', mode === 'vfs' ? 'btn-primary' : 'btn-outline']">Virtual FS</button>
                </div>
                
                <template v-if="mode === 'vfs'">
                    <select v-model="selectedVfs" class="vfs-select">
                        <option value="">Select VFS...</option>
                        <option v-for="vfs in vfsList" :key="vfs.path" :value="vfs.path">{{ vfs.name }}</option>
                    </select>
                    <button @click="createVfs" class="btn btn-sm btn-secondary">New VFS</button>
                </template>
                
                <div class="spacer"></div>
                
                <div class="btn-group">
                    <button @click="createItem('folder')" class="btn btn-sm btn-outline" title="New Folder"><LucideIcon name="folder-plus" size="18"/></button>
                    <button @click="createItem('file')" class="btn btn-sm btn-outline" title="New File"><LucideIcon name="file-plus" size="18"/></button>
                    <button @click="refresh" class="btn btn-sm btn-outline" title="Refresh"><LucideIcon name="refresh-cw" size="18"/></button>
                </div>
            </div>
        </div>

        <div class="explorer-layout">
            <aside class="explorer-sidebar">
                <div v-if="loading" class="loading-state">Loading...</div>
                <TreeView 
                    :items="treeItems" 
                    :selectedPath="selectedPath" 
                    :selectedId="selectedId"
                    @select="onSelect" 
                    @toggle="onToggle"
                    @move="onMove"
                    @contextmenu="onContextMenu"
                />
            </aside>
            
            <main class="explorer-main">
                <div v-if="!selectedItem" class="empty-state">
                    <LucideIcon name="mouse-pointer-2" size="48" />
                    <p>Select a file to view or edit</p>
                </div>
                
                <template v-else>
                    <ImageEditor 
                        v-if="isImage" 
                        :src="itemUrl" 
                        :path="selectedPath"
                        @save="refresh"
                    />
                    <VideoEditor
                        v-else-if="isVideo && viewMode === 'edit'"
                        :src="itemUrl"
                        :path="selectedItem.path"
                        :fileName="selectedItem.name"
                        @save="refresh"
                    />
                    <VideoPlayer
                        v-else-if="isVideo"
                        :src="itemUrl"
                        :fileName="selectedItem.name"
                    />
                    
                    <div v-else-if="isJson" style="height: 100%; display: flex; flex-direction: column;">
                         <div class="editor-view-toggle">
                            <button @click="viewMode = 'code'" :class="{active: viewMode === 'code'}">Code</button>
                            <button @click="viewMode = 'tree'" :class="{active: viewMode === 'tree'}">Tree</button>
                        </div>
                        
                        <JsonEditor
                            v-if="viewMode === 'tree'"
                            v-model="currentItemContent"
                            @save="saveContent"
                        />
                        <FileEditor 
                            v-else
                            v-model="currentItemContent" 
                            :filePath="selectedPath || selectedItem.name"
                            language="json"
                            @save="saveContent"
                        />
                    </div>

                    <FileEditor 
                        v-else 
                        v-model="currentItemContent" 
                        :filePath="selectedPath || selectedItem.name"
                        :readOnly="selectedItem.isDir || selectedItem.type === 'folder'"
                        @save="saveContent"
                    />

                    <!-- Video View/Edit Toggle -->
                    <div v-if="isVideo" class="editor-view-toggle">
                        <button @click="viewMode = 'view'" :class="{active: viewMode === 'view'}">Player</button>
                        <button @click="viewMode = 'edit'" :class="{active: viewMode === 'edit'}">Editor</button>
                    </div>
                </template>
            </main>
        </div>
        
        <div v-if="contextMenu.visible" class="context-menu" :style="contextMenuStyle">
            <button @click="renameItem">Rename</button>
            <button @click="deleteItem" class="btn-danger">Delete</button>
        </div>
    </div>
    `,
    setup() {
        const mode = ref('real');
        const loading = ref(false);
        const treeItems = ref([]);
        const vfsList = ref([]);
        const selectedVfs = ref('');
        const selectedItem = ref(null);
        const currentItemContent = ref('');
        const contextMenu = ref({ visible: false, x: 0, y: 0, item: null });
        const viewMode = ref('code'); // code | tree | view | edit

        const selectedPath = computed(() => selectedItem.value?.path || '');
        const selectedId = computed(() => selectedItem.value?.id || 0);

        const isImage = computed(() => {
            if (!selectedItem.value) return false;
            const ext = selectedItem.value.name.split('.').pop().toLowerCase();
            return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
        });

        const isVideo = computed(() => {
            if (!selectedItem.value) return false;
            const ext = selectedItem.value.name.split('.').pop().toLowerCase();
            return ['mp4', 'webm', 'ogg'].includes(ext);
        });

        const isJson = computed(() => {
            if (!selectedItem.value) return false;
            return selectedItem.value.name.toLowerCase().endsWith('.json');
        });

        const itemUrl = computed(() => {
            if (mode.value === 'vfs' || !selectedPath.value) return '';

            const params = new URLSearchParams({
                path: selectedPath.value
            });
            return '/@/file-explorer/preview?' + params.toString();
        });

        const loadVfsList = async () => {
            const res = await fetch('/@/file-explorer/vfs');
            if (res.ok) vfsList.value = await res.json();
        };

        const refresh = async () => {
            loading.value = true;
            try {
                const params = new URLSearchParams({
                    type: mode.value,
                    vfsDb: selectedVfs.value
                });
                const res = await fetch('/@/file-explorer/list?' + params);
                if (res.ok) treeItems.value = await res.json();
            } finally {
                loading.value = false;
            }
        };

        const onToggle = async (item) => {
            item.expanded = !item.expanded;
            if (item.expanded && !item.children) {
                item.loading = true;
                try {
                    const params = new URLSearchParams({
                        type: mode.value,
                        vfsDb: selectedVfs.value,
                        path: item.path || '',
                        parentId: item.id || 0
                    });
                    const res = await fetch('/@/file-explorer/list?' + params);
                    if (res.ok) {
                        item.children = await res.json();
                    }
                } finally {
                    item.loading = false;
                }
            }
        };

        const onSelect = async (item) => {
            selectedItem.value = item;

            // Should reset viewMode appropriately
            if (item.name.endsWith('.json')) viewMode.value = 'code';
            else if (['mp4', 'webm'].some(ext => item.name.endsWith(ext))) viewMode.value = 'view';

            if (item.isDir || item.type === 'folder' || isImage.value || isVideo.value) {
                return;
            }

            const params = new URLSearchParams({
                type: mode.value,
                vfsDb: selectedVfs.value,
                path: item.path,
                id: item.id
            });
            const res = await fetch('/@/file-explorer/read?' + params);
            if (res.ok) {
                const data = await res.json();
                currentItemContent.value = data.content || '';
            }
        };

        const saveContent = async () => {
            const res = await fetch('/@/file-explorer/write', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type: mode.value,
                    vfsDb: selectedVfs.value,
                    path: selectedPath.value,
                    id: selectedId.value,
                    content: currentItemContent.value
                })
            });
            if (res.ok) alert('Saved successfully');
        };

        const createItem = async (type) => {
            const name = prompt(`Enter ${type} name:`);
            if (!name) return;

            const res = await fetch('/@/file-explorer/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type: mode.value,
                    vfsDb: selectedVfs.value,
                    path: selectedPath.value ? selectedPath.value + '/' + name : name,
                    parentId: selectedId.value,
                    name: name,
                    itemType: type
                })
            });
            if (res.ok) refresh();
        };

        const onMove = async ({ source, target }) => {
            const res = await fetch('/@/file-explorer/move', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type: mode.value,
                    vfsDb: selectedVfs.value,
                    id: source.id,
                    source: source.path,
                    target: (target.path || '') + '/' + source.name,
                    newParentId: target.id
                })
            });
            if (res.ok) refresh();
        };

        const onContextMenu = (e, item) => {
            contextMenu.value = {
                visible: true,
                x: e.clientX,
                y: e.clientY,
                item: item
            };
            window.addEventListener('click', hideContextMenu, { once: true });
        };

        const hideContextMenu = () => {
            contextMenu.value.visible = false;
        };

        const deleteItem = async () => {
            const item = contextMenu.value.item;
            if (!confirm(`Delete ${item.name}?`)) return;

            const res = await fetch('/@/file-explorer/delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type: mode.value,
                    vfsDb: selectedVfs.value,
                    id: item.id,
                    path: item.path
                })
            });
            if (res.ok) refresh();
        };

        const renameItem = async () => {
            const item = contextMenu.value.item;
            const newName = prompt('Enter new name:', item.name);
            if (!newName) return;

            const res = await fetch('/@/file-explorer/rename', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type: mode.value,
                    vfsDb: selectedVfs.value,
                    id: item.id,
                    oldPath: item.path,
                    newPath: item.path.substring(0, item.path.lastIndexOf('/') + 1) + newName,
                    newName: newName
                })
            });
            if (res.ok) refresh();
        };

        const createVfs = async () => {
            const name = prompt('VFS Name:');
            if (!name) return;
            const res = await fetch('/@/file-explorer/vfs', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name })
            });
            if (res.ok) {
                await loadVfsList();
                const data = await res.json();
                selectedVfs.value = data.path;
                mode.value = 'vfs';
            }
        };

        const contextMenuStyle = computed(() => ({
            top: contextMenu.value.y + 'px',
            left: contextMenu.value.x + 'px'
        }));

        watch([mode, selectedVfs], refresh);

        onMounted(() => {
            refresh();
            loadVfsList();

            // Inject CSS
            if (!document.getElementById('file-explorer-css')) {
                const link = document.createElement('link');
                link.id = 'file-explorer-css';
                link.rel = 'stylesheet';
                link.href = '/min/css/plugins/FileExplorer/resources/js/FileExplorer.css';
                document.head.appendChild(link);
            }
        });

        return {
            mode, loading, treeItems, vfsList, selectedVfs, selectedItem, currentItemContent,
            selectedPath, selectedId, isImage, isVideo, isJson, itemUrl, contextMenu, contextMenuStyle,
            viewMode, onSelect, onToggle, onMove, onContextMenu, saveContent, createItem, deleteItem, renameItem, createVfs, refresh
        };
    }
};
