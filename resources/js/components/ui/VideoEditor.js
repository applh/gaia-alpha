import { ref, onMounted } from 'vue';
import Icon from 'ui/Icon.js';

export default {
    name: 'VideoEditor',
    components: { LucideIcon: Icon },
    props: {
        src: { type: String, required: true },
        path: { type: String, required: true },
        fileName: { type: String, default: '' },
        processUrl: { type: String, default: '/@/file-explorer/video-process' }
    },
    emits: ['save'],
    template: `
    <div class="video-editor">
        <div class="editor-toolbar">
            <div class="file-name">
                <LucideIcon name="video" size="16" />
                {{ fileName }} (Editor)
            </div>
            <div class="actions">
                <button @click="extractFrame" class="btn btn-sm" :disabled="loading">
                    <LucideIcon name="image" size="14" /> Extract Frame
                </button>
                <button @click="trimVideo" class="btn btn-sm" :disabled="loading">
                    <LucideIcon name="scissors" size="14" /> Trim
                </button>
                <button @click="compressVideo" class="btn btn-sm btn-primary" :disabled="loading">
                    <LucideIcon name="minimize" size="14" /> Compress
                </button>
            </div>
        </div>
        
        <div class="video-editor-body">
            <div class="video-preview">
                 <video ref="video" :src="src" controls @timeupdate="onTimeUpdate"></video>
            </div>
            
            <div class="editor-controls">
                <div class="control-group">
                    <label>Action Settings</label>
                    <div class="field">
                        <span>Current Time: {{ currentTime }}s</span>
                    </div>
                </div>

                <div class="control-group">
                    <label>Trim Settings</label>
                    <div class="field">
                        <span>Start:</span>
                        <input v-model="trimStart" placeholder="00:00:00" class="form-control" />
                    </div>
                    <div class="field">
                        <span>Duration (s):</span>
                        <input v-model="trimDuration" placeholder="10" class="form-control" />
                    </div>
                </div>

                <div class="control-group">
                    <label>Compression (CRF)</label>
                    <div class="field">
                        <input type="range" v-model="crf" min="0" max="51" step="1" />
                        <span>{{ crf }} (Low is better)</span>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="loading" class="loading-overlay">
            <div class="spinner"></div>
            <span>Processing Video...</span>
        </div>
    </div>
    `,
    setup(props, { emit }) {
        const video = ref(null);
        const loading = ref(false);
        const currentTime = ref(0);
        const trimStart = ref('00:00:00');
        const trimDuration = ref('10');
        const crf = ref(28);

        const onTimeUpdate = () => {
            if (video.value) {
                currentTime.value = Math.floor(video.value.currentTime);
            }
        };

        const extractFrame = async () => {
            const timeStr = new Date(video.value.currentTime * 1000).toISOString().substr(11, 8);
            await processAction('extract-frame', { time: timeStr });
        };

        const trimVideo = async () => {
            await processAction('trim', { start: trimStart.value, duration: trimDuration.value });
        };

        const compressVideo = async () => {
            await processAction('compress', { crf: crf.value });
        };

        const processAction = async (action, extra = {}) => {
            loading.value = true;
            try {
                const res = await fetch(props.processUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action,
                        path: props.path,
                        ...extra
                    })
                });
                const result = await res.json();
                if (result.success) {
                    alert('Operation successful: ' + result.path);
                    emit('save');
                } else {
                    alert('Operation failed: ' + (result.error || 'Unknown error'));
                }
            } catch (error) {
                alert('Error processing video: ' + error.message);
            } finally {
                loading.value = false;
            }
        };

        return {
            video,
            loading,
            currentTime,
            trimStart,
            trimDuration,
            crf,
            onTimeUpdate,
            extractFrame,
            trimVideo,
            compressVideo
        };
    }
};
