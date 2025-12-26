
import { ref, computed } from 'vue';

const MlPca = {
    template: `
        <div class="p-6">
            <h1 class="text-2xl font-bold mb-6">Principal Component Analysis (PCA)</h1>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Data Input Panel -->
                <div class="md:col-span-1 bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold mb-4">Input Data</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Paste CSV Data</label>
                            <p class="text-xs text-gray-500 mb-1">Row = Observation, Col = Feature. First row ignored if headers.</p>
                            <textarea v-model="inputRaw" rows="10" 
                                class="w-full font-mono text-xs p-2 border rounded focus:ring-blue-500 focus:border-blue-500"
                                placeholder="1.2, 3.4, 5.1&#10;2.1, 4.3, 6.0..."></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Has Header Row?</label>
                            <input type="checkbox" v-model="hasHeader" class="ml-1">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Number of Components</label>
                            <input type="number" v-model="nComponents" min="1" max="10" 
                                class="mt-1 block w-24 rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <button @click="runAnalysis" :disabled="loading"
                            class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 disabled:opacity-50 flex justify-center items-center">
                            <span v-if="loading"><i class="fas fa-spinner fa-spin mr-2"></i> Analyzing...</span>
                            <span v-else>Run PCA</span>
                        </button>
                        
                        <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded text-sm">
                            {{ error }}
                        </div>
                    </div>
                </div>

                <!-- Results Panel -->
                <div class="md:col-span-2 space-y-6">
                    <!-- Explain Variance -->
                    <div v-if="result" class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-4">Explained Variance</h2>
                        <div class="flex space-x-2">
                            <div v-for="(val, idx) in result.eigenvalues" :key="idx" class="flex-1 bg-gray-50 p-2 rounded text-center">
                                <span class="block text-xs text-gray-500">PC{{ idx + 1 }}</span>
                                <span class="block font-mono font-bold">{{ formatNumber(val) }}</span>
                                <span class="block text-xs text-blue-600">{{ formatPercent(val / totalVariance) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Visualization (Scatter Plot for first 2 dimensions) -->
                    <div v-if="result && result.projected && result.projected[0].length >= 2" class="bg-white rounded-lg shadow p-6 relative">
                         <h2 class="text-lg font-semibold mb-4">2D Projection (PC1 vs PC2)</h2>
                         <div class="border rounded bg-gray-50 h-[400px] relative overflow-hidden flex items-center justify-center">
                            <!-- Simple SVG Scatter Plot -->
                            <svg viewBox="0 0 100 100" class="w-full h-full p-4">
                                <!-- Axes -->
                                <line x1="0" y1="50" x2="100" y2="50" stroke="#ddd" stroke-width="0.5" />
                                <line x1="50" y1="0" x2="50" y2="100" stroke="#ddd" stroke-width="0.5" />
                                
                                <!-- Points -->
                                <circle v-for="(point, idx) in normalizedPoints" :key="idx"
                                    :cx="point.x" :cy="point.y" r="1.5" fill="#2563EB" opacity="0.6">
                                    <title>Row {{ idx + 1 }}: [{{ formatNumber(result.projected[idx][0]) }}, {{ formatNumber(result.projected[idx][1]) }}]</title>
                                </circle>
                            </svg>
                         </div>
                    </div>
                    
                    <!-- Raw Output -->
                     <div v-if="result" class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold mb-2">Projected Data (First 5 Rows)</h2>
                        <div class="overflow-x-auto">
                             <table class="min-w-full text-sm font-mono text-left">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-2">#</th>
                                        <th v-for="i in nComponents" :key="i" class="p-2">PC{{i}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, rIdx) in result.projected.slice(0, 5)" :key="rIdx" class="border-b">
                                        <td class="p-2 text-gray-500">{{ rIdx + 1 }}</td>
                                        <td v-for="(val, cIdx) in row" :key="cIdx" class="p-2">{{ formatNumber(val) }}</td>
                                    </tr>
                                </tbody>
                             </table>
                             <p v-if="result.projected.length > 5" class="text-xs text-gray-500 mt-2">...and {{ result.projected.length - 5 }} more rows.</p>
                        </div>
                     </div>
                </div>
            </div>
        </div>
    `,
    setup() {

        const inputRaw = ref("5.1,3.5,1.4,0.2\n4.9,3.0,1.4,0.2\n4.7,3.2,1.3,0.2\n4.6,3.1,1.5,0.2\n5.0,3.6,1.4,0.2");
        const hasHeader = ref(false);
        const nComponents = ref(2);
        const loading = ref(false);
        const error = ref(null);
        const result = ref(null);

        const formatNumber = (n) => typeof n === 'number' ? n.toFixed(4) : n;
        const formatPercent = (n) => (n * 100).toFixed(1) + '%';

        const totalVariance = computed(() => {
            if (!result.value) return 1;
            return result.value.eigenvalues.reduce((a, b) => a + b, 0);
        });

        // Computed points for SVG scatter plot (Normalize to 0-100 range)
        const normalizedPoints = computed(() => {
            if (!result.value || !result.value.projected) return [];
            const points = result.value.projected.map(r => ({ x: r[0], y: r[1] }));

            // Find Min/Max
            const xVals = points.map(p => p.x);
            const yVals = points.map(p => p.y);
            const minX = Math.min(...xVals);
            const maxX = Math.max(...xVals);
            const minY = Math.min(...yVals);
            const maxY = Math.max(...yVals);

            // Scale to 10-90 (keep padding)
            // If all points are same, avoid div/0
            const dx = maxX - minX || 1;
            const dy = maxY - minY || 1;

            return points.map(p => ({
                x: 10 + ((p.x - minX) / dx) * 80,
                y: 90 - ((p.y - minY) / dy) * 80 // Flip Y for SVG
            }));
        });

        const parseCSV = (text) => {
            const lines = text.trim().split('\n');
            const data = [];
            let start = hasHeader.value ? 1 : 0;

            for (let i = start; i < lines.length; i++) {
                const line = lines[i].trim();
                if (!line) continue;
                const row = line.split(',').map(s => parseFloat(s.trim()));
                if (row.some(isNaN)) throw new Error(`Invalid number at row ${i + 1}`);
                data.push(row);
            }
            return data;
        };

        const runAnalysis = async () => {
            loading.value = true;
            error.value = null;
            result.value = null;

            try {
                const data = parseCSV(inputRaw.value);
                if (data.length < 2) throw new Error("Need at least 2 rows of data");

                const res = await fetch('/@/ml-pca/analyze', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ data, components: nComponents.value })
                });

                const json = await res.json();

                if (json.success) {
                    result.value = json.result;
                } else {
                    error.value = json.error || 'Unknown error';
                }
            } catch (e) {
                error.value = e.message;
            } finally {
                loading.value = false;
            }
        };

        return {
            inputRaw, hasHeader, nComponents, loading, error, result,
            runAnalysis, formatNumber, formatPercent,
            totalVariance, normalizedPoints
        };
    }
};

// Expose globally for dynamic loading
// window.MlPca = MlPca;
export default MlPca;
