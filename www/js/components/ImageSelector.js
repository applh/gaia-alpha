import { ref, reactive, onMounted, computed, watch, defineAsyncComponent } from 'vue';
import Modal from './Modal.js';
import Icon from './Icon.js';

export default {
    components: { Modal, LucideIcon: Icon },
    props: {
        show: Boolean,
        multiple: Boolean
    },
    emits: ['close', 'select'],
    template: `
        <Modal :show="show" title="Select Image" @close="$emit('close')">
            <div class="image-selector">
                <div class="selector-tabs">
                    <button @click="activeTab = 'gallery'" :class="{ active: activeTab === 'gallery' }">Media Library</button>
                    <button @click="activeTab = 'upload'" :class="{ active: activeTab === 'upload' }">Upload New</button>
                </div>

                <!-- Gallery View -->
                <div v-if="activeTab === 'gallery'" class="gallery-view">
                    <div v-if="loading" class="loading">Loading images...</div>
                    <div v-else-if="images.length === 0" class="empty-state">No images found. Import some!</div>
                    <div v-else class="image-grid">
                        <div v-for="img in images" :key="img.id" 
                            class="image-item" 
                            :class="{ selected: isSelected(img) }"
                            @click="selectImage(img)">
                            <img :src="img.image + '?w=200&h=200&fit=cover'" :alt="img.title" loading="lazy">
                            <div class="image-meta">{{ img.title || img.slug }}</div>
                            <div v-if="isSelected(img)" class="check-mark">
                                <LucideIcon name="check-circle" size="24" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upload View -->
                <div v-if="activeTab === 'upload'" class="upload-view">
                    <div 
                        class="drop-zone" 
                        :class="{ dragging: isDragging }"
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="handleDrop"
                        @click="$refs.fileInput.click()">
                        
                        <div v-if="uploading" class="upload-status">
                            <LucideIcon name="loader" class="spin" size="48" />
                            <p>Uploading...</p>
                        </div>
                        <div v-else>
                            <LucideIcon name="upload-cloud" size="48" style="color: var(--text-secondary);" />
                            <p>Click or Drag images here to upload</p>
                            <span class="sub-text">Supports JPG, PNG, WEBP, AVIF</span>
                        </div>
                        <input type="file" ref="fileInput" @change="handleFileSelect" style="display: none" accept="image/*" multiple>
                    </div>
                </div>

                <div class="selector-footer">
                    <button @click="$emit('close')" class="btn-secondary">Cancel</button>
                    <button 
                        @click="confirmSelection" 
                        class="btn-primary" 
                        :disabled="!currentSelection">
                        Select {{ currentSelection ? 'Image' : '' }}
                    </button>
                </div>
            </div>
        </Modal>
    `,
    styles: `
        .image-selector {
            min-width: 600px;
            max-width: 90vw;
            height: 70vh;
            display: flex;
            flex-direction: column;
        }
        .selector-tabs {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 15px;
        }
        .selector-tabs button {
            background: none;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            color: var(--text-secondary);
        }
        .selector-tabs button.active {
            border-bottom-color: var(--primary-color);
            color: var(--primary-color);
            font-weight: bold;
        }
        .gallery-view, .upload-view {
            flex: 1;
            overflow-y: auto;
            min-height: 0;
            padding: 10px;
        }
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
        }
        .image-item {
            position: relative;
            aspect-ratio: 1;
            border: 2px solid transparent;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--bg-secondary);
        }
        .image-item:hover {
            border-color: var(--primary-color-dim);
        }
        .image-item.selected {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px var(--primary-color-dim);
        }
        .image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .image-meta {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.6);
            color: white;
            padding: 4px;
            font-size: 0.75rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .check-mark {
            position: absolute;
            top: 5px;
            right: 5px;
            color: var(--primary-color);
            background: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .drop-zone {
            height: 100%;
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            color: var(--text-secondary);
        }
        .drop-zone:hover, .drop-zone.dragging {
            border-color: var(--primary-color);
            background: var(--bg-hover);
            color: var(--primary-color);
        }
        .sub-text {
            font-size: 0.8rem;
            opacity: 0.7;
            margin-top: 8px;
        }
        .selector-footer {
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .spin {
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 100% {transform: rotate(360deg);} }
    `,
    setup(props, { emit }) {
        const activeTab = ref('gallery');
        const images = ref([]);
        const loading = ref(false);
        const uploading = ref(false);
        const isDragging = ref(false);
        const currentSelection = ref(null);

        // Fetch images when modal opens
        watch(() => props.show, (val) => {
            if (val) {
                fetchImages();
                currentSelection.value = null;
                activeTab.value = 'gallery';
            }
        });

        const fetchImages = async () => {
            loading.value = true;
            try {
                const res = await fetch('/api/cms/pages?cat=image');
                if (res.ok) {
                    images.value = await res.json();
                }
            } catch (e) {
                console.error(e);
            } finally {
                loading.value = false;
            }
        };

        const selectImage = (img) => {
            currentSelection.value = img;
            // If not multiple, we could potentially emit immediately or double click
        };

        const isSelected = (img) => {
            return currentSelection.value && currentSelection.value.id === img.id;
        };

        const confirmSelection = () => {
            if (currentSelection.value) {
                emit('select', currentSelection.value);
                emit('close');
            }
        };

        const handleUpload = async (files) => {
            if (!files || files.length === 0) return;

            uploading.value = true;
            try {
                // Support multiple files sequentially
                for (let i = 0; i < files.length; i++) {
                    const formData = new FormData();
                    formData.append('image', files[i]);

                    const res = await fetch('/api/cms/upload', {
                        method: 'POST',
                        body: formData
                    });

                    if (res.ok) {
                        // Refresh gallery
                        await fetchImages();
                        activeTab.value = 'gallery';

                        // Select the newly uploaded image (it should be first if sorted by created_at desc)
                        // Assuming API returns list sorted by created desc
                        // But we don't know the exact ID without looking at response URL and matching
                        // For now just switching to gallery is good UX
                    } else {
                        const err = await res.json();
                        alert('Upload failed: ' + (err.error || 'Unknown'));
                    }
                }
            } catch (e) {
                console.error(e);
                alert('Upload failed');
            } finally {
                uploading.value = false;
            }
        };

        const handleDrop = (e) => {
            isDragging.value = false;
            const files = e.dataTransfer.files;
            handleUpload(files);
        };

        const handleFileSelect = (e) => {
            const files = e.target.files;
            handleUpload(files);
            e.target.value = ''; // Reset input
        };

        return {
            activeTab, images, loading, uploading, isDragging, currentSelection,
            fetchImages, selectImage, isSelected, confirmSelection,
            handleDrop, handleFileSelect
        };
    }
};
