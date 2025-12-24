import { ref, onMounted, computed } from 'vue';
import Icon from 'ui/Icon.js';
import ImageEditor from 'ui/ImageEditor.js';
import VideoPlayer from 'ui/VideoPlayer.js';
import VideoEditor from 'ui/VideoEditor.js';

export default {
    components: { LucideIcon: Icon, ImageEditor, VideoPlayer, VideoEditor },

    template: `
    <div class="media-library-page">
        <div class="admin-header">
            <h2 class="page-title">
                <LucideIcon name="image" size="32" />
                Media Library
            </h2>
            <div class="button-group">
                <button @click="viewMode = 'grid'" :class="{'btn-primary': viewMode === 'grid'}" class="btn">
                    <LucideIcon name="grid" size="16" /> Grid
                </button>
                <button @click="viewMode = 'list'" :class="{'btn-primary': viewMode === 'list'}" class="btn">
                    <LucideIcon name="list" size="16" /> List
                </button>
                <label class="btn btn-primary" style="cursor: pointer; margin: 0;">
                    <LucideIcon name="upload" size="16" /> Upload
                    <input type="file" @change="handleFileUpload" accept="image/*" multiple style="display: none;">
                </label>
            </div>
        </div>

        <!-- Stats Bar -->
        <div class="stats-bar" v-if="stats">
            <div class="stat-item">
                <LucideIcon name="file" size="16" />
                <span>{{ stats.total_files }} files</span>
            </div>
            <div class="stat-item">
                <LucideIcon name="hard-drive" size="16" />
                <span>{{ stats.total_size_formatted }}</span>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="admin-card" style="margin-bottom: 20px;">
            <div class="search-filters">
                <div class="search-box">
                    <LucideIcon name="search" size="16" />
                    <input 
                        v-model="searchQuery" 
                        @input="debouncedSearch"
                        placeholder="Search files..." 
                        class="search-input"
                    />
                </div>
                <div class="tag-filters">
                    <button 
                        @click="selectedTag = null" 
                        :class="{'active': selectedTag === null}"
                        class="tag-filter-btn"
                    >
                        All Files
                    </button>
                    <button 
                        v-for="tag in tags" 
                        :key="tag.id"
                        @click="selectedTag = tag.slug"
                        :class="{'active': selectedTag === tag.slug}"
                        :style="{borderColor: tag.color}"
                        class="tag-filter-btn"
                    >
                        {{ tag.name }} ({{ tag.media_count }})
                    </button>
                    <button @click="showTagModal = true" class="btn btn-sm">
                        <LucideIcon name="plus" size="14" /> New Tag
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="admin-card">
            <div class="loading-state">Loading media files...</div>
        </div>

        <!-- Empty State -->
        <div v-else-if="filteredFiles.length === 0" class="admin-card">
            <div class="empty-state">
                <LucideIcon name="image" size="48" />
                <p>No media files found</p>
                <p class="text-muted">Upload your first file to get started</p>
            </div>
        </div>

        <!-- Grid View -->
        <div v-else-if="viewMode === 'grid'" class="media-grid">
            <div 
                v-for="file in filteredFiles" 
                :key="file.id"
                @click="selectFile(file)"
                :class="{'selected': selectedFiles.includes(file.id)}"
                class="media-card"
            >
                <div class="media-thumbnail">
                    <img v-if="isImage(file)" :src="getFileUrl(file)" :alt="file.alt_text || file.original_filename" />
                    <div v-else class="media-icon-placeholder">
                        <LucideIcon v-if="isVideo(file)" name="film" size="48" />
                        <LucideIcon v-else name="file" size="48" />
                    </div>
                    <div class="media-overlay">
                        <input 
                            type="checkbox" 
                            :checked="selectedFiles.includes(file.id)"
                            @click.stop="toggleSelection(file.id)"
                        />
                    </div>
                </div>
                <div class="media-info">
                    <div class="media-title">{{ file.original_filename }}</div>
                    <div class="media-meta">{{ formatFileSize(file.file_size) }}</div>
                    <div class="media-tags" v-if="file.tags">
                        <span v-for="tagName in file.tags.split(',')" :key="tagName" class="tag-badge">
                            {{ tagName }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- List View -->
        <div v-else class="admin-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="40">
                            <input type="checkbox" @change="toggleSelectAll" :checked="allSelected" />
                        </th>
                        <th>Preview</th>
                        <th>Filename</th>
                        <th>Type</th>
                        <th>Size</th>
                        <th>Tags</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="file in filteredFiles" :key="file.id">
                        <td>
                            <input 
                                type="checkbox" 
                                :checked="selectedFiles.includes(file.id)"
                                @change="toggleSelection(file.id)"
                            />
                        </td>
                        <td>
                            <img :src="getFileUrl(file)" class="thumbnail" />
                        </td>
                        <td>{{ file.original_filename }}</td>
                        <td>{{ file.mime_type }}</td>
                        <td>{{ formatFileSize(file.file_size) }}</td>
                        <td>
                            <span v-for="tagName in (file.tags || '').split(',')" :key="tagName" class="tag-badge">
                                {{ tagName }}
                            </span>
                        </td>
                        <td>{{ formatDate(file.created_at) }}</td>
                        <td>
                            <button @click="editFile(file)" class="btn btn-sm">
                                <LucideIcon name="edit" size="14" />
                            </button>
                            <button @click="deleteFile(file.id)" class="btn btn-sm btn-danger">
                                <LucideIcon name="trash" size="14" />
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Bulk Actions Bar -->
        <div v-if="selectedFiles.length > 0" class="bulk-actions-bar">
            <span>{{ selectedFiles.length }} file(s) selected</span>
            <button @click="bulkTag" class="btn btn-sm">
                <LucideIcon name="tag" size="14" /> Tag Selected
            </button>
            <button @click="bulkDelete" class="btn btn-sm btn-danger">
                <LucideIcon name="trash" size="14" /> Delete Selected
            </button>
            <button @click="selectedFiles = []" class="btn btn-sm">Clear</button>
        </div>

        <!-- Edit Modal -->
        <div v-if="editingFile" class="modal-overlay" @click="editingFile = null">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h3>Edit Media</h3>
                    <button @click="editingFile = null" class="btn-close">×</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Filename</label>
                        <input v-model="editingFile.original_filename" class="form-control" />
                    </div>
                    <div class="form-group">
                        <label>Alt Text</label>
                        <input v-model="editingFile.alt_text" class="form-control" placeholder="Describe the image" />
                    </div>
                    <div class="form-group">
                        <label>Caption</label>
                        <textarea v-model="editingFile.caption" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Tags</label>
                        <div class="tag-selector">
                            <label v-for="tag in tags" :key="tag.id" class="tag-checkbox">
                                <input 
                                    type="checkbox" 
                                    :value="tag.id"
                                    v-model="editingFileTags"
                                />
                                <span :style="{color: tag.color}">{{ tag.name }}</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="editingFile = null" class="btn">Cancel</button>
                    <button @click="saveFile" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </div>

        <!-- Tag Creation Modal -->
        <div v-if="showTagModal" class="modal-overlay" @click="showTagModal = false">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h3>Create Tag</h3>
                    <button @click="showTagModal = false" class="btn-close">×</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Tag Name</label>
                        <input v-model="newTag.name" class="form-control" placeholder="e.g., Product Photos" />
                    </div>
                    <div class="form-group">
                        <label>Color</label>
                        <input v-model="newTag.color" type="color" class="form-control" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="showTagModal = false" class="btn">Cancel</button>
                    <button @click="createTag" class="btn btn-primary">Create Tag</button>
                </div>
                <div class="modal-footer">
                    <button @click="showTagModal = false" class="btn">Cancel</button>
                    <button @click="createTag" class="btn btn-primary">Create Tag</button>
                </div>
            </div>
        </div>

        <!-- Image/Video Editor Modal -->
        <div v-if="mediaEditor.file" class="modal-overlay" @click="mediaEditor.file = null">
            <div class="modal-content large-modal" @click.stop>
                <div class="modal-header">
                    <h3>Edit Media</h3>
                    <button @click="mediaEditor.file = null" class="btn-close">×</button>
                </div>
                <div class="modal-body editor-body">
                    <ImageEditor
                        v-if="isImage(mediaEditor.file)"
                        :src="getFileUrl(mediaEditor.file)"
                        :path="mediaEditor.file.path"
                        processUrl="/@/media-library/process-image"
                        @save="onMediaSaved"
                    />
                    <div v-else-if="isVideo(mediaEditor.file)" class="video-editor-wrapper">
                         <div class="editor-view-toggle in-modal">
                            <button @click="mediaEditor.mode = 'view'" :class="{active: mediaEditor.mode === 'view'}">Player</button>
                            <button @click="mediaEditor.mode = 'edit'" :class="{active: mediaEditor.mode === 'edit'}">Editor</button>
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
            </div>
        </div>
    </div>
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
