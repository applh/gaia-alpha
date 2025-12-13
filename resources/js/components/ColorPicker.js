import { ref, computed, watch, onMounted } from 'vue';

export default {
    name: 'ColorPicker',
    props: {
        modelValue: {
            type: String,
            default: ''
        },
        palette: {
            type: Array,
            default: () => ['#FF6B6B', '#4ECDC4', '#FFE66D', '#1A535C', '#F7FFF7']
        }
    },
    emits: ['update:modelValue'],
    template: `
        <div class="color-picker" @click.stop>
            <div class="picker-tabs">
                <button 
                    @click="mode = 'palette'" 
                    :class="{ active: mode === 'palette' }"
                >Palette</button>
                <button 
                    @click="mode = 'custom'" 
                    :class="{ active: mode === 'custom' }"
                >Custom</button>
            </div>

            <div v-if="mode === 'palette'" class="palette-grid">
                <div 
                    v-for="color in palette" 
                    :key="color"
                    class="palette-swatch"
                    :style="{ backgroundColor: color }"
                    :class="{ selected: modelValue === color }"
                    @click="selectColor(color)"
                ></div>
                <div 
                    class="palette-swatch clear-swatch"
                    :class="{ selected: !modelValue }"
                    @click="selectColor('')"
                    title="Clear Color"
                >×</div>
            </div>

            <div v-else class="custom-picker">
                <div class="preview-box" :style="{ backgroundColor: customHex }"></div>
                
                <div class="slider-group">
                    <label>Hue</label>
                    <input type="range" v-model.number="hue" min="0" max="360" class="hue-slider">
                </div>
                <div class="slider-group">
                    <label>Saturation</label>
                    <input type="range" v-model.number="saturation" min="0" max="100">
                </div>
                <div class="slider-group">
                    <label>Value</label>
                    <input type="range" v-model.number="value" min="0" max="100">
                </div>
                
                <div class="hex-input">
                    <input v-model="customHex" @input="updateFromHex" placeholder="#RRGGBB">
                    <button @click="confirmCustom" class="btn-small">Select</button>
                </div>
            </div>
        </div>
    `,
    setup(props, { emit }) {
        const mode = ref('palette');
        const hue = ref(0);
        const saturation = ref(100);
        const value = ref(100);
        const customHex = ref(props.modelValue || '#FFFFFF');

        // RGB to HSV conversion for initialization
        const hexToHsv = (hex) => {
            let r = 0, g = 0, b = 0;
            if (hex.length === 4) {
                r = "0x" + hex[1] + hex[1];
                g = "0x" + hex[2] + hex[2];
                b = "0x" + hex[3] + hex[3];
            } else if (hex.length === 7) {
                r = "0x" + hex[1] + hex[2];
                g = "0x" + hex[3] + hex[4];
                b = "0x" + hex[5] + hex[6];
            }
            r /= 255; g /= 255; b /= 255;
            let cmin = Math.min(r, g, b), cmax = Math.max(r, g, b), delta = cmax - cmin;
            let h = 0, s = 0, v = 0;

            if (delta == 0) h = 0;
            else if (cmax == r) h = ((g - b) / delta) % 6;
            else if (cmax == g) h = (b - r) / delta + 2;
            else h = (r - g) / delta + 4;

            h = Math.round(h * 60);
            if (h < 0) h += 360;

            l = (cmax + cmin) / 2;
            s = delta == 0 ? 0 : delta / (1 - Math.abs(2 * l - 1));
            s = +(s * 100).toFixed(1);
            l = +(l * 100).toFixed(1);

            // Wait, I need HSV not HSL
            // V = cmax
            v = cmax * 100;
            s = cmax === 0 ? 0 : (delta / cmax) * 100;

            return { h, s, v };
        };

        const hsvToHex = (h, s, v) => {
            s /= 100;
            v /= 100;
            let c = v * s;
            let x = c * (1 - Math.abs(((h / 60) % 2) - 1));
            let m = v - c;
            let r = 0, g = 0, b = 0;

            if (0 <= h && h < 60) { r = c; g = x; b = 0; }
            else if (60 <= h && h < 120) { r = x; g = c; b = 0; }
            else if (120 <= h && h < 180) { r = 0; g = c; b = x; }
            else if (180 <= h && h < 240) { r = 0; g = x; b = c; }
            else if (240 <= h && h < 300) { r = x; g = 0; b = c; }
            else if (300 <= h && h < 360) { r = c; g = 0; b = x; }

            r = Math.round((r + m) * 255).toString(16).padStart(2, '0');
            g = Math.round((g + m) * 255).toString(16).padStart(2, '0');
            b = Math.round((b + m) * 255).toString(16).padStart(2, '0');

            return `#${r}${g}${b}`.toUpperCase();
        };

        // Watch sliders to update hex
        watch([hue, saturation, value], () => {
            customHex.value = hsvToHex(hue.value, saturation.value, value.value);
        });

        const selectColor = (color) => {
            emit('update:modelValue', color);
        };

        const confirmCustom = () => {
            emit('update:modelValue', customHex.value);
        };

        const updateFromHex = () => {
            if (/^#[0-9A-F]{6}$/i.test(customHex.value)) {
                const { h, s, v } = hexToHsv(customHex.value);
                hue.value = h;
                saturation.value = s;
                value.value = v;
                emit('update:modelValue', customHex.value); // Real-time update?
            }
        };

        onMounted(() => {
            if (props.modelValue) {
                const { h, s, v } = hexToHsv(props.modelValue);
                hue.value = h;
                saturation.value = s;
                value.value = v;
                customHex.value = props.modelValue;
            }
        });

        return {
            mode,
            hue,
            saturation,
            value,
            customHex,
            selectColor,
            confirmCustom,
            updateFromHex
        };
    }
}
