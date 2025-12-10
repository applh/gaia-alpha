
import { ref, onMounted, nextTick } from 'vue';

export default {
    template: `
        <div class="map-container" style="height: calc(100vh - 80px); position: relative;">
            <div id="leaflet-map" style="height: 100%; width: 100%; z-index: 1;"></div>
            
            <!-- Floating Action Button for Adding Marker (if needed, but click on map is better) -->
            <!-- Modal for new marker -->
            <div v-if="showModal" class="modal-overlay" style="z-index: 1000;">
                <div class="modal">
                    <h3>New Marker</h3>
                    <input v-model="newMarkerLabel" placeholder="Enter label" @keyup.enter="saveMarker" ref="labelInput" />
                    <div style="margin-top: 10px; display: flex; justify-content: flex-end; gap: 10px;">
                        <button @click="closeModal">Cancel</button>
                        <button @click="saveMarker" class="primary">Save</button>
                    </div>
                </div>
            </div>
        </div>
    `,
    setup() {
        const map = ref(null);
        const markers = ref([]);
        const showModal = ref(false);
        const newMarkerLabel = ref('');
        const newMarkerPos = ref(null);
        const labelInput = ref(null);

        const initMap = async () => {
            // Lazy load CSS
            if (!document.getElementById('leaflet-css')) {
                const link = document.createElement('link');
                link.id = 'leaflet-css';
                link.rel = 'stylesheet';
                link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                document.head.appendChild(link);
            }

            // Lazy load Leaflet ESM
            // We use esm.sh for convenient ESM access to npm packages
            const L_module = await import('https://esm.sh/leaflet@1.9.4');
            const L = L_module.default || L_module;

            // Default to some location (e.g., London) or user's location if possible
            map.value = L.map('leaflet-map').setView([51.505, -0.09], 13);

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map.value);

            map.value.on('click', (e) => onMapClick(e, L));

            loadMarkers(L);
        };

        const onMapClick = (e, L) => {
            newMarkerPos.value = e.latlng;
            newMarkerLabel.value = '';
            showModal.value = true;
            nextTick(() => {
                if (labelInput.value) labelInput.value.focus();
            });
        };

        const loadMarkers = async (L) => {
            try {
                const res = await fetch('/api/markers');
                if (res.ok) {
                    const data = await res.json();
                    markers.value = data;
                    renderMarkers(L);
                }
            } catch (e) {
                console.error("Failed to load markers", e);
            }
        };

        const renderMarkers = (L) => {
            // Clear existing markers if we were strictly managing them, 
            // but for now we just add them. A crude clear operation:
            // In a real app we'd track marker layers to remove them.
            // For this simple version, we assume loadMarkers is called once.

            markers.value.forEach(m => {
                L.marker([m.lat, m.lng])
                    .addTo(map.value)
                    .bindPopup(m.label);
            });
        };

        const saveMarker = async () => {
            if (!newMarkerLabel.value.trim() || !newMarkerPos.value) return;

            const payload = {
                label: newMarkerLabel.value,
                lat: newMarkerPos.value.lat,
                lng: newMarkerPos.value.lng
            };

            try {
                const res = await fetch('/api/markers', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                if (res.ok) {
                    const result = await res.json();

                    // Ensure L is available
                    const L_module = await import('https://esm.sh/leaflet@1.9.4');
                    const L = L_module.default || L_module;

                    L.marker([payload.lat, payload.lng])
                        .addTo(map.value)
                        .bindPopup(payload.label)
                        .openPopup();

                    closeModal();
                } else {
                    alert('Failed to save marker');
                }
            } catch (e) {
                console.error("Error saving marker", e);
            }
        };

        const closeModal = () => {
            showModal.value = false;
            newMarkerPos.value = null;
        };

        onMounted(() => {
            // Wait for DOM
            nextTick(() => {
                initMap();
            });
        });

        return { showModal, newMarkerLabel, saveMarker, closeModal, labelInput };
    }
};
