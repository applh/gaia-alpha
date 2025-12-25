import { ref, onMounted, computed, watch } from 'vue';
import { store } from 'store';
import Icon from 'ui/Icon.js';
import UIButton from 'ui/Button.js';
import Card from 'ui/Card.js';
import Container from 'ui/Container.js';
import Row from 'ui/Row.js';
import Col from 'ui/Col.js';
import Spinner from 'ui/Spinner.js';
import { UITitle, UIText } from 'ui/Typography.js';
import TreeView from 'ui/TreeView.js';
import FileEditor from './components/FileEditor.js';
import ImageEditor from 'ui/ImageEditor.js';
import VideoPlayer from 'ui/VideoPlayer.js';
import VideoEditor from 'ui/VideoEditor.js';
import JsonEditor from './components/JsonEditor.js';

export default {
    components: {
        LucideIcon: Icon,
        'ui-button': UIButton,
        'ui-card': Card,
        'ui-container': Container,
        'ui-row': Row,
        'ui-col': Col,
        'ui-spinner': Spinner,
        'ui-title': UITitle,
        'ui-text': UIText,
        TreeView,
        FileEditor,
        ImageEditor,
        VideoPlayer,
        VideoEditor,
        JsonEditor
    },
    template: `
    <ui-container class="file-explorer-container">
        <div class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
            <ui-title :level="1">
                <LucideIcon name="folder-tree" size="28" style="margin-right: 12px; vertical-align: middle; color: var(--accent-color);" />
                File Explorer
            </ui-title>
            <div style="display: flex; gap: 12px; align-items: center;">
                <div style="background: var(--bg-secondary); padding: 4px; border-radius: 12px; display: flex; gap: 4px;">
                    <ui-button :variant="mode === 'real' ? 'primary' : 'secondary'" size="sm" @click="mode = 'real'">Real FS</ui-button>
                    <ui-button :variant="mode === 'vfs' ? 'primary' : 'secondary'" size="sm" @click="mode = 'vfs'">Virtual FS</ui-button>
                </div>
                
                <template v-if="mode === 'vfs'">
                    <select v-model="selectedVfs" style="background: var(--card-bg); color: var(--text-color); border: 1px solid var(--border-color); padding: 6px 12px; border-radius: 8px; cursor: pointer;">
                        <option value="">Select VFS...</option>
                        <option v-for="vfs in vfsList" :key="vfs.path" :value="vfs.path">{{ vfs.name }}</option>
                    </select>
                    <ui-button size="sm" @click="createVfs">New VFS</ui-button>
                </template>
                
                <div style="display: flex; gap: 8px;">
                    <ui-button size="sm" @click="createItem('folder')" title="New Folder"><LucideIcon name="folder-plus" size="18"/></ui-button>
                    <ui-button size="sm" @click="createItem('file')" title="New File"><LucideIcon name="file-plus" size="18"/></ui-button>
                    <ui-button size="sm" @click="refresh" title="Refresh"><LucideIcon name="refresh-cw" size="18"/></ui-button>
                </div>
            </div>
        </div>

        <ui-row :gutter="20" style="height: calc(100vh - 200px); min-height: 500px;">
            <!-- Sidebar -->
            <ui-col :span="6" style="height: 100%;">
                <ui-card style="height: 100%; display: flex; flex-direction: column; overflow: hidden; padding: 0;">
                    <div style="padding: 16px; border-bottom: 1px solid var(--border-color);">
                        <ui-text weight="bold" size="sm" style="text-transform: uppercase; color: var(--text-muted);">Structure</ui-text>
                    </div>
                    <div style="flex: 1; overflow-y: auto; padding: 12px;">
                        <ui-spinner v-if="loading" style="display: block; margin: 40px auto;" />
                        <TreeView 
                            v-else
                            :items="treeItems" 
                            idKey="path" 
                            childrenKey="children"
                            labelKey="name"
                            :selectedId="selectedPath" 
                            :draggable="true"
                            :allowDrop="allowDrop"
                            @select="onSelect" 
                            @toggle="onToggle"
                            @move="onMove"
                            @contextmenu="onContextMenu"
                        >
                            <template #item="{ item }">
                                 <div style="display: flex; align-items: center; gap: 8px;">
                                    <LucideIcon :name="getIcon(item)" size="16" style="color: var(--accent-color);" />
                                    <span style="font-size: 0.9rem;">{{ item.name }}</span>
                                    <LucideIcon v-if="item.loading" name="refresh-cw" size="12" class="spin" />
                                </div>
                            </template>
                        </TreeView>
                    </div>
                </ui-card>
            </ui-col>
            
            <!-- Main Content -->
            <ui-col :span="18" style="height: 100%;">
                <ui-card style="height: 100%; padding: 0; display: flex; flex-direction: column; overflow: hidden;">
                    <div v-if="!selectedItem" style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; opacity: 0.2;">
                        <LucideIcon name="mouse-pointer-2" size="64" style="margin-bottom: 24px;" />
                        <ui-title :level="3">Select a file to view or edit</ui-title>
                    </div>
                    
                    <template v-else>
                        <div style="padding: 12px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                            <ui-text weight="bold">{{ selectedItem.name }}</ui-text>
                            <div v-if="isVideo || isJson" style="background: var(--bg-secondary); padding: 4px; border-radius: 8px; display: flex; gap: 4px;">
                                <template v-if="isVideo">
                                    <ui-button :variant="viewMode === 'view' ? 'primary' : 'secondary'" size="sm" @click="viewMode = 'view'">Player</ui-button>
                                    <ui-button :variant="viewMode === 'edit' ? 'primary' : 'secondary'" size="sm" @click="viewMode = 'edit'">Editor</ui-button>
                                </template>
                                <template v-else-if="isJson">
                                    <ui-button :variant="viewMode === 'code' ? 'primary' : 'secondary'" size="sm" @click="viewMode = 'code'">Code</ui-button>
                                    <ui-button :variant="viewMode === 'tree' ? 'primary' : 'secondary'" size="sm" @click="viewMode = 'tree'">Tree</ui-button>
                                </template>
                            </div>
                        </div>

                        <div style="flex: 1; overflow: hidden; position: relative;">
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
                            
                            <template v-else-if="isJson">
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
                            </template>

                            <FileEditor 
                                v-else 
                                v-model="currentItemContent" 
                                :filePath="selectedPath || selectedItem.name"
                                :readOnly="selectedItem.isDir || selectedItem.type === 'folder'"
                                @save="saveContent"
                            />
                        </div>
                    </template>
                </ui-card>
            </ui-col>
        </ui-row>
        
        <!-- Context Menu -->
        <div v-if="contextMenu.visible" class="context-menu" :style="contextMenuStyle" style="position: fixed; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); min-width: 150px; z-index: 1000; overflow: hidden;">
            <div style="padding: 4px;">
                <button @click="renameItem" style="width: 100%; text-align: left; padding: 8px 12px; border: none; background: none; color: var(--text-color); cursor: pointer; border-radius: 4px; font-size: 0.9rem;" onmouseover="this.style.background='var(--bg-secondary)'" onmouseout="this.style.background='none'">Rename</button>
                <div style="height: 1px; background: var(--border-color); margin: 4px 0;"></div>
                <button @click="deleteItem" style="width: 100%; text-align: left; padding: 8px 12px; border: none; background: none; color: #ef4444; cursor: pointer; border-radius: 4px; font-size: 0.9rem;" onmouseover="this.style.background='rgba(239, 68, 68, 0.1)'" onmouseout="this.style.background='none'">Delete</button>
            </div>
        </div>
    </ui-container>
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
            if (res.ok) store.addNotification('File saved successfully', 'success');
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
            if (res.ok) {
                store.addNotification(`${type} created successfully`, 'success');
                refresh();
            }
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
            if (res.ok) {
                store.addNotification('Item moved successfully', 'success');
                refresh();
            }
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
            if (res.ok) {
                store.addNotification('Item deleted successfully', 'success');
                refresh();
            }
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
            if (res.ok) {
                store.addNotification('Item renamed successfully', 'success');
                refresh();
            }
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
                store.addNotification('VFS created successfully', 'success');
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

        const getIcon = (item) => {
            if (item.isDir || item.type === 'folder') return 'folder';
            const ext = item.ext || (item.name.includes('.') ? item.name.split('.').pop() : '');
            switch (ext.toLowerCase()) {
                case 'php': return 'code-2';
                case 'js': return 'scroll';
                case 'css': return 'palette';
                case 'json': return 'braces';
                case 'md': return 'file-text';
                case 'png':
                case 'jpg':
                case 'jpeg':
                case 'webp': return 'image';
                case 'sql':
                case 'sqlite': return 'database';
                default: return 'file';
            }
        };

        const allowDrop = (sourceId, target) => {
            return target.isDir || target.type === 'folder';
        };

        onMounted(() => {
            refresh();
            loadVfsList();
        });

        return {
            mode, loading, treeItems, vfsList, selectedVfs, selectedItem, currentItemContent,
            selectedPath, selectedId, isImage, isVideo, isJson, itemUrl, contextMenu, contextMenuStyle,
            viewMode, onSelect, onToggle, onMove, onContextMenu, saveContent, createItem, deleteItem, renameItem, createVfs, refresh,
            getIcon, allowDrop
        };
    }
};
