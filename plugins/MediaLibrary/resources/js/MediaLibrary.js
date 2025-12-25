import { ref, onMounted, computed, reactive, h } from 'vue';
import { store } from 'store';
import Icon from 'ui/Icon.js';
import UIButton from 'ui/Button.js';
import Card from 'ui/Card.js';
import Container from 'ui/Container.js';
import Row from 'ui/Row.js';
import Col from 'ui/Col.js';
import DataTable from 'ui/DataTable.js';
import Modal from 'ui/Modal.js';
import Input from 'ui/Input.js';
import Textarea from 'ui/Textarea.js';
import Tag from 'ui/Tag.js';
import Checkbox from 'ui/Checkbox.js';
import { UITitle, UIText } from 'ui/Typography.js';
import ImageEditor from 'ui/ImageEditor.js';
import VideoPlayer from 'ui/VideoPlayer.js';
import VideoEditor from 'ui/VideoEditor.js';

export default {
    components: {
        LucideIcon: Icon,
        'ui-button': UIButton,
        'ui-card': Card,
        'ui-container': Container,
        'ui-row': Row,
        'ui-col': Col,
        'ui-data-table': DataTable,
        'ui-modal': Modal,
        'ui-input': Input,
        'ui-textarea': Textarea,
        'ui-tag': Tag,
        'ui-checkbox': Checkbox,
        'ui-title': UITitle,
        'ui-text': UIText,
        ImageEditor,
        VideoPlayer,
        VideoEditor
    },

    template: `
    <ui-container class="media-library-page">
        <div class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
            <ui-title :level="1">
                <LucideIcon name="image" size="28" style="margin-right: 12px; vertical-align: middle; color: var(--accent-color);" />
                Media Library
            </ui-title>
            <div style="display: flex; gap: 8px; align-items: center;">
                <ui-button :variant="viewMode === 'grid' ? 'primary' : 'default'" @click="viewMode = 'grid'">
                    <LucideIcon name="grid" size="16" />
                </ui-button>
                <ui-button :variant="viewMode === 'list' ? 'primary' : 'default'" @click="viewMode = 'list'">
                    <LucideIcon name="list" size="16" />
                </ui-button>
                <label style="cursor: pointer; margin: 0;">
                    <ui-button variant="primary">
                        <LucideIcon name="upload" size="16" style="margin-right: 8px;" /> Upload Media
                    </ui-button>
                    <input type="file" @change="handleFileUpload" accept="image/*,video/*" multiple style="display: none;">
                </label>
            </div>
        </div>

        <ui-row :gutter="20" style="margin-bottom: 24px;">
            <ui-col :span="24">
                <ui-card>
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                        <div style="display: flex; gap: 12px; align-items: center; flex: 1; min-width: 300px;">
                            <ui-input 
                                v-model="searchQuery" 
                                @input="debouncedSearch"
                                placeholder="Search files..." 
                                style="max-width: 400px; flex: 1;"
                            >
                                <template #prefix>
                                    <LucideIcon name="search" size="16" />
                                </template>
                            </ui-input>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <ui-tag 
                                    :variant="selectedTag === null ? 'primary' : 'info'" 
                                    style="cursor: pointer;"
                                    @click="selectedTag = null"
                                >
                                    All Files
                                </ui-tag>
                                <ui-tag 
                                    v-for="tag in tags" 
                                    :key="tag.id"
                                    :variant="selectedTag === tag.slug ? 'primary' : 'info'"
                                    :style="selectedTag === tag.slug ? {} : { border: '1px solid ' + tag.color, color: tag.color }"
                                    style="cursor: pointer;"
                                    @click="selectedTag = tag.slug"
                                >
                                    {{ tag.name }} ({{ tag.media_count }})
                                </ui-tag>
                                <ui-button size="sm" @click="showTagModal = true">
                                    <LucideIcon name="plus" size="14" />
                                </ui-button>
                            </div>
                        </div>
                        <div v-if="stats" style="display: flex; gap: 20px; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 8px; color: var(--text-muted); font-size: 0.9rem;">
                                <LucideIcon name="file" size="16" />
                                <span>{{ stats.total_files }} files</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; color: var(--text-muted); font-size: 0.9rem;">
                                <LucideIcon name="hard-drive" size="16" />
                                <span>{{ stats.total_size_formatted }}</span>
                            </div>
                        </div>
                    </div>
                </ui-card>
            </ui-col>
        </ui-row>

        <div v-if="loading" style="text-align: center; padding: 100px;">
            <div class="loading-spinner"></div>
            <ui-text style="margin-top: 16px;">Loading media files...</ui-text>
        </div>

        <ui-card v-else-if="filteredFiles.length === 0" style="text-align: center; padding: 120px 20px;">
            <LucideIcon name="image" size="64" style="opacity: 0.1; margin-bottom: 24px;" />
            <ui-title :level="3">No media files found</ui-title>
            <ui-text class="text-muted">Upload your first file to get started</ui-text>
        </ui-card>

        <ui-row v-else-if="viewMode === 'grid'" :gutter="20">
            <ui-col 
                v-for="file in filteredFiles" 
                :key="file.id"
                :xs="12" :sm="8" :md="6" :lg="4" :xl="3"
                style="margin-bottom: 20px;"
            >
                <ui-card 
                    class="media-card" 
                    :style="selectedFiles.includes(file.id) ? { ring: '2px solid var(--accent-color)', background: 'rgba(99, 102, 241, 0.05)' } : {}"
                    style="padding: 0; overflow: hidden; cursor: pointer; transition: all 0.2s;"
                    @click="selectFile(file)"
                >
                    <div style="position: relative; aspect-ratio: 1; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.05);">
                        <img v-if="isImage(file)" :src="getFileUrl(file)" :alt="file.alt_text || file.original_filename" style="width: 100%; height: 100%; object-fit: cover;" />
                        <div v-else>
                            <LucideIcon v-if="isVideo(file)" name="film" size="48" style="opacity: 0.3;" />
                            <LucideIcon v-else name="file" size="48" style="opacity: 0.3;" />
                        </div>
                        <div style="position: absolute; top: 12px; left: 12px; z-index: 5;">
                            <ui-checkbox :modelValue="selectedFiles.includes(file.id)" @update:modelValue="toggleSelection(file.id)" @click.stop />
                        </div>
                    </div>
                    <div style="padding: 12px;">
                        <ui-text weight="medium" style="display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ file.original_filename }}</ui-text>
                        <ui-text size="extra-small" class="text-muted">{{ formatFileSize(file.file_size) }}</ui-text>
                    </div>
                </ui-card>
            </ui-col>
        </ui-row>

        <ui-card v-else>
            <ui-data-table 
                :data="filteredFiles"
                :columns="[
                    { label: '', width: '50px', render: (row) => h(Checkbox, { modelValue: selectedFiles.includes(row.id), 'onUpdate:modelValue': () => toggleSelection(row.id) }) },
                    { label: 'Preview', width: '80px', render: (row) => h('img', { src: getFileUrl(row), style: 'width: 40px; height: 40px; border-radius: 4px; object-fit: cover;' }) },
                    { label: 'Filename', prop: 'original_filename' },
                    { label: 'Type', prop: 'mime_type', width: '150px' },
                    { label: 'Size', render: (row) => formatFileSize(row.file_size), width: '100px' },
                    { label: 'Created', render: (row) => formatDate(row.created_at), width: '120px' },
                    { label: 'Actions', width: '120px', align: 'right' }
                ]"
            >
                <template #actions="{ row }">
                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                        <ui-button size="sm" @click="editFile(row)">
                            <LucideIcon name="edit" size="14" />
                        </ui-button>
                        <ui-button size="sm" variant="danger" @click="deleteFile(row.id)">
                            <LucideIcon name="trash" size="14" />
                        </ui-button>
                    </div>
                </template>
            </ui-data-table>
        </ui-card>

        <div v-if="selectedFiles.length > 0" class="bulk-actions-bar" style="position: fixed; bottom: 32px; left: 50%; transform: translateX(-50%); background: var(--card-bg); border-radius: 99px; padding: 8px 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); border: 1px solid var(--border-color); display: flex; align-items: center; gap: 16px; z-index: 1000;">
            <ui-text weight="bold">{{ selectedFiles.length }} selected</ui-text>
            <div style="width: 1px; height: 24px; background: var(--border-color); margin: 0 4px;"></div>
            <ui-button size="sm" @click="bulkTag">
                <LucideIcon name="tag" size="14" style="margin-right: 8px;" /> Tag
            </ui-button>
            <ui-button size="sm" variant="danger" @click="bulkDelete">
                <LucideIcon name="trash" size="14" style="margin-right: 8px;" /> Delete
            </ui-button>
            <ui-button size="sm" @click="selectedFiles = []">Clear</ui-button>
        </div>

        <!-- Edit Modal -->
        <ui-modal :show="!!editingFile" title="Edit Media Metadata" @close="editingFile = null">
            <div v-if="editingFile" style="display: flex; flex-direction: column; gap: 20px;">
                <ui-input v-model="editingFile.original_filename" label="Filename" placeholder="e.g. hero-image.jpg" />
                <ui-input v-model="editingFile.alt_text" label="Alt Text" placeholder="Describe for accessibility" />
                <ui-textarea v-model="editingFile.caption" label="Caption" placeholder="Brief description" :rows="3" />
                
                <div>
                    <ui-text weight="medium" style="display: block; margin-bottom: 8px;">Tags</ui-text>
                    <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                        <ui-checkbox 
                            v-for="tag in tags" 
                            :key="tag.id"
                            :label="tag.name"
                            :modelValue="editingFileTags.includes(tag.id)"
                            @update:modelValue="v => v ? editingFileTags.push(tag.id) : editingFileTags = editingFileTags.filter(id => id !== tag.id)"
                        />
                    </div>
                </div>
            </div>
            <template #footer>
                <ui-button @click="editingFile = null">Cancel</ui-button>
                <ui-button variant="primary" @click="saveFile">Save Changes</ui-button>
            </template>
        </ui-modal>

        <!-- Tag Creation Modal -->
        <ui-modal :show="showTagModal" title="Create New Tag" @close="showTagModal = false">
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <ui-input v-model="newTag.name" label="Tag Name" placeholder="e.g. Product Photos" />
                <div>
                    <ui-text weight="medium" style="display: block; margin-bottom: 8px;">Color</ui-text>
                    <input type="color" v-model="newTag.color" style="width: 100%; height: 40px; border: none; border-radius: 8px; cursor: pointer; background: none;" />
                </div>
            </div>
            <template #footer>
                <ui-button @click="showTagModal = false">Cancel</ui-button>
                <ui-button variant="primary" @click="createTag">Create Tag</ui-button>
            </template>
        </ui-modal>

        <!-- Image/Video Editor Modal -->
        <ui-modal :show="!!mediaEditor.file" :title="'Editor: ' + (mediaEditor.file?.original_filename || '')" large @close="mediaEditor.file = null">
            <div v-if="mediaEditor.file" style="min-height: 500px;">
                <ImageEditor
                    v-if="isImage(mediaEditor.file)"
                    :src="getFileUrl(mediaEditor.file)"
                    :path="mediaEditor.file.path"
                    processUrl="/@/media-library/process-image"
                    @save="onMediaSaved"
                />
                <div v-else-if="isVideo(mediaEditor.file)">
                    <div style="display: flex; justify-content: center; margin-bottom: 24px;">
                        <div style="background: var(--bg-secondary); padding: 4px; border-radius: 12px; display: flex; gap: 4px;">
                            <ui-button :variant="mediaEditor.mode === 'view' ? 'primary' : 'default'" size="sm" @click="mediaEditor.mode = 'view'">Player</ui-button>
                            <ui-button :variant="mediaEditor.mode === 'edit' ? 'primary' : 'default'" size="sm" @click="mediaEditor.mode = 'edit'">Editor</ui-button>
                        </div>
                    </div>

                    <VideoPlayer
                        v-if="mediaEditor.mode === 'view'"
                        :src="getFileUrl(mediaEditor.file)"
                        :fileName="mediaEditor.file.original_filename"
                    />
                    <VideoEditor
                        v-else
                        :src="getFileUrl(mediaEditor.file)"
                        :path="mediaEditor.file.path || ''" 
                        :fileName="mediaEditor.file.original_filename"
                        processUrl="/@/media-library/process-video"
                        @save="onMediaSaved"
                    />
                </div>
            </div>
            <template #footer>
                <ui-button @click="mediaEditor.file = null">Close</ui-button>
            </template>
        </ui-modal>
    </ui-container>
    `,


    setup() {
        const files = ref([]);
        const tags = ref([]);
        const stats = ref(null);
        const loading = ref(false);
        const viewMode = ref('grid');
        const searchQuery = ref('');
        const selectedTag = ref(null);
        const selectedFiles = ref([]);
        const editingFile = ref(null);
        const editingFileTags = ref([]);
        const showTagModal = ref(false);
        const newTag = ref({ name: '', color: '#6366f1' });

        const filteredFiles = computed(() => {
            let result = files.value;

            if (selectedTag.value) {
                result = result.filter(f => f.tags && f.tags.includes(selectedTag.value));
            }

            if (searchQuery.value) {
                const query = searchQuery.value.toLowerCase();
                result = result.filter(f =>
                    f.original_filename.toLowerCase().includes(query) ||
                    (f.alt_text && f.alt_text.toLowerCase().includes(query)) ||
                    (f.caption && f.caption.toLowerCase().includes(query))
                );
            }

            return result;
        });

        const allSelected = computed(() => {
            return filteredFiles.value.length > 0 &&
                selectedFiles.value.length === filteredFiles.value.length;
        });

        const loadFiles = async () => {
            loading.value = true;
            try {
                const res = await fetch('/@/media-library/files');
                if (res.ok) {
                    files.value = await res.json();
                }
            } finally {
                loading.value = false;
            }
        };

        const loadTags = async () => {
            const res = await fetch('/@/media-library/tags');
            if (res.ok) {
                tags.value = await res.json();
            }
        };

        const loadStats = async () => {
            const res = await fetch('/@/media-library/stats');
            if (res.ok) {
                stats.value = await res.json();
            }
        };

        const handleFileUpload = async (event) => {
            const uploadFiles = Array.from(event.target.files);

            for (const file of uploadFiles) {
                const formData = new FormData();
                formData.append('file', file);

                try {
                    const res = await fetch('/@/media-library/files', {
                        method: 'POST',
                        body: formData
                    });

                    if (res.ok) {
                        await loadFiles();
                        await loadStats();
                    }
                } catch (error) {
                    console.error('Upload failed:', error);
                }
            }

            event.target.value = '';
        };

        const selectFile = (file) => {
            // Check if we should open editor or just edit metadata
            if (isImage(file) || isVideo(file)) {
                mediaEditor.value = { file: file, mode: 'view' };
            } else {
                editFile(file);
            }
        };

        const toggleSelection = (fileId) => {
            const index = selectedFiles.value.indexOf(fileId);
            if (index > -1) {
                selectedFiles.value.splice(index, 1);
            } else {
                selectedFiles.value.push(fileId);
            }
        };

        const toggleSelectAll = () => {
            if (allSelected.value) {
                selectedFiles.value = [];
            } else {
                selectedFiles.value = filteredFiles.value.map(f => f.id);
            }
        };

        const editFile = (file) => {
            editingFile.value = { ...file };
            editingFileTags.value = file.tags ? file.tags.split(',').map(name => {
                const tag = tags.value.find(t => t.name === name.trim());
                return tag ? tag.id : null;
            }).filter(Boolean) : [];
        };

        const saveFile = async () => {
            if (!editingFile.value) return;

            // Update metadata
            await fetch(`/@/media-library/files/${editingFile.value.id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    original_filename: editingFile.value.original_filename,
                    alt_text: editingFile.value.alt_text,
                    caption: editingFile.value.caption
                })
            });

            // Update tags
            await fetch(`/@/media-library/files/${editingFile.value.id}/tags`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ tag_ids: editingFileTags.value })
            });

            editingFile.value = null;
            await loadFiles();
        };

        const deleteFile = async (fileId) => {
            if (!confirm('Are you sure you want to delete this file?')) return;

            await fetch(`/@/media-library/files/${fileId}`, { method: 'DELETE' });
            await loadFiles();
            await loadStats();
        };

        const createTag = async () => {
            if (!newTag.value.name) return;

            await fetch('/@/media-library/tags', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(newTag.value)
            });

            newTag.value = { name: '', color: '#6366f1' };
            showTagModal.value = false;
            await loadTags();
        };

        const bulkTag = async () => {
            // Simple implementation: open first file for editing
            if (selectedFiles.value.length > 0) {
                const file = files.value.find(f => f.id === selectedFiles.value[0]);
                if (file) editFile(file);
            }
        };

        const bulkDelete = async () => {
            if (!confirm(`Delete ${selectedFiles.value.length} file(s)?`)) return;

            for (const fileId of selectedFiles.value) {
                await fetch(`/@/media-library/files/${fileId}`, { method: 'DELETE' });
            }

            selectedFiles.value = [];
            await loadFiles();
            await loadStats();
        };

        const getFileUrl = (file) => {
            return `/media/${file.user_id}/${file.filename}?w=400&h=400&fit=cover`;
        };

        const formatFileSize = (bytes) => {
            const units = ['B', 'KB', 'MB', 'GB'];
            let size = bytes;
            let unitIndex = 0;
            while (size >= 1024 && unitIndex < units.length - 1) {
                size /= 1024;
                unitIndex++;
            }
            return `${size.toFixed(1)} ${units[unitIndex]}`;
        };

        const formatDate = (dateStr) => {
            return new Date(dateStr).toLocaleDateString();
        };

        let searchTimeout;
        const debouncedSearch = () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // Search is handled by computed property
            }, 300);
        };

        // Media Editor State
        const mediaEditor = ref({ file: null, mode: 'view' });

        const isImage = (file) => {
            if (!file) return false;
            return file.mime_type.startsWith('image/');
        };

        const isVideo = (file) => {
            if (!file) return false;
            return file.mime_type.startsWith('video/');
        };

        const onMediaSaved = async () => {
            mediaEditor.value.file = null;
            await loadFiles();
        };

        onMounted(() => {
            loadFiles();
            loadTags();
            loadStats();
        });

        return {
            files,
            tags,
            stats,
            loading,
            viewMode,
            searchQuery,
            selectedTag,
            selectedFiles,
            editingFile,
            editingFileTags,
            showTagModal,
            newTag,
            filteredFiles,
            allSelected,
            handleFileUpload,
            selectFile,
            toggleSelection,
            toggleSelectAll,
            editFile,
            saveFile,
            deleteFile,
            createTag,
            bulkTag,
            bulkDelete,
            getFileUrl,
            formatFileSize,
            formatDate,
            debouncedSearch,
            mediaEditor,
            isImage,
            isVideo,
            onMediaSaved
        };
    }
};
