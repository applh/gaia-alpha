import { ref, onMounted, watch } from 'vue';
import Icon from 'ui/Icon.js';

export default {
    name: 'ImageEditor',
    components: { LucideIcon: Icon },
    props: {
        src: { type: String, required: true },
        path: { type: String, required: true }
    },
    emits: ['save'],
    template: `
    <div class="image-editor">
        <div class="editor-toolbar">
            <div class="tool-group">
                <button @click="rotate(-90)" title="Rotate Left"><LucideIcon name="rotate-ccw" size="18"/></button>
                <button @click="rotate(90)" title="Rotate Right"><LucideIcon name="rotate-cw" size="18"/></button>
                <button @click="flip('h')" title="Flip Horizontal"><LucideIcon name="flip-horizontal" size="18"/></button>
                <button @click="flip('v')" title="Flip Vertical"><LucideIcon name="flip-vertical" size="18"/></button>
            </div>
            <div class="tool-group">
                <select v-model="filter" @change="applyPreview">
                    <option value="">No Filter</option>
                    <option value="grayscale">Grayscale</option>
                    <option value="negate">Invert</option>
                    <option value="brightness:50">Brighter</option>
                    <option value="brightness:-50">Darker</option>
                    <option value="contrast:50">Higher Contrast</option>
                </select>
            </div>
            <button @click="save" class="btn btn-primary btn-sm">Save Changes</button>
        </div>
        
        <div class="canvas-container">
            <canvas ref="canvas"></canvas>
            <div v-if="cropping" class="crop-overlay" :style="cropStyle"></div>
        </div>
    </div>
    `,
    setup(props, { emit }) {
        const canvas = ref(null);
        const filter = ref('');
        const rotation = ref(0);
        const flipH = ref(1);
        const flipV = ref(1);
        const cropping = ref(false);
        const img = new Image();

        const loadImg = () => {
            img.src = props.src + '?t=' + Date.now();
            img.onload = () => {
                const ctx = canvas.value.getContext('2d');
                canvas.value.width = img.width;
                canvas.value.height = img.height;
                applyPreview();
            };
        };

        const applyPreview = () => {
            const ctx = canvas.value.getContext('2d');
            canvas.value.width = (rotation.value % 180 === 0) ? img.width : img.height;
            canvas.value.height = (rotation.value % 180 === 0) ? img.height : img.width;

            ctx.clearRect(0, 0, canvas.value.width, canvas.value.height);
            ctx.save();
            ctx.translate(canvas.value.width / 2, canvas.value.height / 2);
            ctx.rotate(rotation.value * Math.PI / 180);
            ctx.scale(flipH.value, flipV.value);

            if (filter.value === 'grayscale') ctx.filter = 'grayscale(100%)';
            else if (filter.value === 'negate') ctx.filter = 'invert(100%)';
            else if (filter.value.startsWith('brightness')) ctx.filter = `brightness(${filter.value.split(':')[1]}%)`;

            ctx.drawImage(img, -img.width / 2, -img.height / 2);
            ctx.restore();
        };

        const rotate = (deg) => {
            rotation.value = (rotation.value + deg) % 360;
            applyPreview();
        };

        const flip = (axis) => {
            if (axis === 'h') flipH.value *= -1;
            else flipV.value *= -1;
            applyPreview();
        };

        const save = async () => {
            const payload = {
                src: props.path,
                rotate: rotation.value,
                flip: (flipH.value === -1 ? 'h' : '') + (flipV.value === -1 ? 'v' : ''),
                filter: filter.value
            };

            const res = await fetch('/@/file-explorer/image-process', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (res.ok) {
                emit('save');
            }
        };

        onMounted(loadImg);
        watch(() => props.src, loadImg);

        return { canvas, filter, rotate, flip, save, applyPreview };
    }
};
