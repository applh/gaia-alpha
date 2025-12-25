import { ref, onMounted, onUnmounted, computed } from 'vue';
import Icon from 'ui/Icon.js';
import { marked } from 'marked';

const STYLES = `
    .player-overlay {
        position: fixed;
        top: 0; left: 0;
        width: 100vw; height: 100vh;
        background: #000;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        color: white;
    }
    .player-body {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 2rem;
    }
    .player-slide {
        width: 100%;
        max-width: 1920px;
        aspect-ratio: 16/9;
        background: white;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
    }
    .player-controls {
        position: absolute;
        bottom: 2rem;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 1rem;
        background: rgba(0,0,0,0.5);
        padding: 0.5rem 1.5rem;
        border-radius: 30px;
        backdrop-filter: blur(10px);
        opacity: 0;
        transition: opacity 0.3s;
    }
    .player-overlay:hover .player-controls { opacity: 1; }
    .player-close {
        position: absolute;
        top: 1rem; right: 1rem;
        cursor: pointer;
        opacity: 0.5;
        transition: opacity 0.2s;
    }
    .player-close:hover { opacity: 1; }
    .slide-progress {
        position: absolute;
        bottom: 0; left: 0;
        height: 4px;
        background: var(--accent-color);
        transition: width 0.3s;
    }
    .markdown-slide {
        padding: 4rem;
        width: 100%;
        height: 100%;
        overflow-y: auto;
        color: black;
        text-align: left;
        font-size: 1.5rem;
    }
    .markdown-slide h1 { font-size: 3em; margin-bottom: 0.5em; border-bottom: 1px solid #eee; padding-bottom: 0.2em; }
    .markdown-slide h2 { font-size: 2.5em; margin-bottom: 0.5em; }
    .markdown-slide p { margin-bottom: 1em; line-height: 1.6; }
    .markdown-slide ul, .markdown-slide ol { margin-left: 1.5em; margin-bottom: 1em; }
    .markdown-slide code { background: #eee; padding: 0.2em 0.4em; border-radius: 4px; font-size: 0.9em; }
    .markdown-slide pre { background: #eee; padding: 1em; border-radius: 8px; overflow-x: auto; font-size: 0.8em; }
    .markdown-slide blockquote { border-left: 8px solid #ccc; padding-left: 1em; color: #555; }
`;

export default {
    components: { LucideIcon: Icon },
    props: {
        slides: { type: Array, required: true },
        onClose: { type: Function, required: true }
    },
    template: `
    <div class="player-overlay" @keydown.esc="onClose">
        <div class="player-close" @click="onClose">
            <LucideIcon name="x" size="32" />
        </div>
        
        <div class="player-body">
            <div class="player-slide">
                <template v-if="currentSlide">
                    <img v-if="(!currentSlide.slide_type || currentSlide.slide_type === 'drawing') && currentSlide.content" :src="currentSlide.content" class="w-full h-full object-contain" />
                    <div v-else-if="currentSlide.slide_type === 'markdown'" class="markdown-slide" v-html="renderSlide(currentSlide.content)"></div>
                    <div v-else class="text-black">Empty Slide</div>
                </template>
                <div class="slide-progress" :style="{ width: ((currentIndex + 1) / slides.length * 100) + '%' }"></div>
            </div>
        </div>

        <div class="player-controls">
            <button @click="prev" :disabled="currentIndex === 0" class="btn btn-ghost text-white">
                <LucideIcon name="chevron-left" size="24" />
            </button>
            <span class="flex items-center px-4 font-bold">{{ currentIndex + 1 }} / {{ slides.length }}</span>
            <button @click="next" :disabled="currentIndex === slides.length - 1" class="btn btn-ghost text-white">
                <LucideIcon name="chevron-right" size="24" />
            </button>
        </div>
    </div>
    `,
    setup(props) {
        const currentIndex = ref(0);
        const currentSlide = computed(() => props.slides[currentIndex.value]);

        const renderSlide = (text) => {
            if (!text) return '';
            try { return marked.parse(text); } catch (e) { return ''; }
        };

        const next = () => {
            if (currentIndex.value < props.slides.length - 1) {
                currentIndex.value++;
            }
        };

        const prev = () => {
            if (currentIndex.value > 0) {
                currentIndex.value--;
            }
        };

        const handleKeydown = (e) => {
            if (e.key === 'ArrowRight' || e.key === ' ') next();
            if (e.key === 'ArrowLeft') prev();
            if (e.key === 'Escape') props.onClose();
        };

        onMounted(() => {
            window.addEventListener('keydown', handleKeydown);
            const styleId = 'slides-player-styles';
            if (!document.getElementById(styleId)) {
                const style = document.createElement('style');
                style.id = styleId;
                style.textContent = STYLES;
                document.head.appendChild(style);
            }
        });

        onUnmounted(() => {
            window.removeEventListener('keydown', handleKeydown);
        });

        return { currentIndex, currentSlide, next, prev, renderSlide };
    }
}
