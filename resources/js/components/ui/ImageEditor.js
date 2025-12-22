import { ref, onMounted, watch } from 'vue';
import Icon from 'ui/Icon.js';

export default {
    name: 'ImageEditor',
    components: { LucideIcon: Icon },
    props: {
        src: { type: String, required: true },
        path: { type: String, required: true },
        processUrl: { type: String, default: '/@/file-explorer/image-process' }
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
                <button @click="applyFilter('grayscale')" title="Grayscale"><LucideIcon name="image" size="18"/></button>
                <button @click="applyFilter('invert')" title="Invert"><LucideIcon name="aperture" size="18"/></button>
                <button @click="reset" title="Reset"><LucideIcon name="undo" size="18"/></button>
            </div>
            <div class="spacer"></div>
            <button @click="save" class="btn btn-primary" :disabled="saving">
                <LucideIcon name="save" size="18"/> {{ saving ? 'Saving...' : 'Save' }}
            </button>
        </div>
        <div class="canvas-container">
            <canvas ref="canvas"></canvas>
        </div>
    </div>
    `,
    setup(props, { emit }) {
        const canvas = ref(null);
        const ctx = ref(null);
        const image = ref(null);
        const saving = ref(false);

        // Transform state
        const rotation = ref(0);
        const scaleX = ref(1);
        const scaleY = ref(1);
        const filter = ref('none'); // none, grayscale, invert

        const loadImage = () => {
            image.value = new Image();
            image.value.crossOrigin = "anonymous";
            image.value.onload = () => draw();
            image.value.src = props.src;
        };

        const draw = () => {
            if (!canvas.value || !image.value) return;
            const c = canvas.value;
            const context = c.getContext('2d');
            ctx.value = context;

            // Handle rotation dimensions
            if (rotation.value % 180 !== 0) {
                c.width = image.value.height;
                c.height = image.value.width;
            } else {
                c.width = image.value.width;
                c.height = image.value.height;
            }

            context.save();
            context.clearRect(0, 0, c.width, c.height);

            // Translate to center
            context.translate(c.width / 2, c.height / 2);
            context.rotate(rotation.value * Math.PI / 180);
            context.scale(scaleX.value, scaleY.value);

            // Draw image centered
            context.drawImage(image.value, -image.value.width / 2, -image.value.height / 2);

            // Filters
            if (filter.value === 'grayscale') {
                const imageData = context.getImageData(0, 0, c.width, c.height); // Note: get/putImageData ignores transforms
                const data = imageData.data;
                for (let i = 0; i < data.length; i += 4) {
                    const avg = (data[i] + data[i + 1] + data[i + 2]) / 3;
                    data[i] = avg;
                    data[i + 1] = avg;
                    data[i + 2] = avg;
                }
                context.putImageData(imageData, 0, 0); // This might not work perfectly with rotation, better use CSS filters or separate canvas
            } else if (filter.value === 'invert') {
                const imageData = context.getImageData(0, 0, c.width, c.height);
                const data = imageData.data;
                for (let i = 0; i < data.length; i += 4) {
                    data[i] = 255 - data[i];
                    data[i + 1] = 255 - data[i + 1];
                    data[i + 2] = 255 - data[i + 2];
                }
                context.putImageData(imageData, 0, 0);
            }

            context.restore();
        };

        const rotate = (deg) => { rotation.value = (rotation.value + deg) % 360; draw(); };
        const flip = (axis) => {
            if (axis === 'h') scaleX.value *= -1;
            else scaleY.value *= -1;
            draw();
        };
        const applyFilter = (f) => { filter.value = f; draw(); };
        const reset = () => {
            rotation.value = 0;
            scaleX.value = 1;
            scaleY.value = 1;
            filter.value = 'none';
            draw();
        };

        const save = async () => {
            saving.value = true;
            try {
                // Get data URL
                const dataUrl = canvas.value.toDataURL('image/jpeg', 0.9);

                const res = await fetch(props.processUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        path: props.path,
                        image: dataUrl
                    })
                });

                if (res.ok) {
                    emit('save');
                } else {
                    alert('Failed to save image');
                }
            } catch (e) {
                console.error(e);
                alert('Error saving image');
            } finally {
                saving.value = false;
            }
        };

        watch(() => props.src, loadImage);
        onMounted(loadImage);

        return {
            canvas, rotate, flip, applyFilter, reset, save, saving
        };
    }
};
