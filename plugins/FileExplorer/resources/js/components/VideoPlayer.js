import { ref, watch } from 'vue';
import Icon from 'ui/Icon.js';

export default {
    name: 'VideoPlayer',
    components: { LucideIcon: Icon },
    props: {
        src: { type: String, required: true },
        fileName: { type: String, default: '' }
    },
    template: `
    <div class="video-player">
        <div class="editor-toolbar">
            <div class="file-name">
                <LucideIcon name="video" size="16" />
                {{ fileName }} (Player)
            </div>
        </div>
        <div class="video-container">
            <video ref="video" :src="src" controls></video>
        </div>
    </div>
    `
};
