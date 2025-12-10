
import { ref, onMounted, nextTick } from 'vue';

export default {
    template: `
        <div class="map-page" style="height: calc(100vh - 80px); display: flex; flex-direction: column;">
            <div id="leaflet-map" style="flex: 1; min-height: 50%; width: 100%; z-index: 1;"></div>
            
            <div class="markers-table-container" style="flex: 1; overflow-y: auto; padding: 20px; background: #fff; color: #333;">
                <h3>Markers</h3>
                <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                    <thead>
                        <tr style="border-bottom: 2px solid #eee; text-align: left;">
                            <th style="padding: 10px;">ID</th>
                            <th style="padding: 10px;">Label</th>
                            <th style="padding: 10px;">Position</th>
                            <th style="padding: 10px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="marker in markers" :key="marker.id" style="border-bottom: 1px solid #eee;">
                            <td style="padding: 10px;">{{ marker.id }}</td>
                            <td style="padding: 10px;">{{ marker.label }}</td>
                            <td style="padding: 10px;">{{ marker.lat.toFixed(4) }}, {{ marker.lng.toFixed(4) }}</td>
                            <td style="padding: 10px;">
                                <button @click="centerOnMarker(marker)" style="padding: 5px 10px; cursor: pointer;">View</button>
                            </td>
                        </tr>
                        <tr v-if="markers.length === 0">
                            <td colspan="4" style="padding: 20px; text-align: center; color: #666;">No markers yet. Click on the map to add one.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

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
                link.href = '/js/vendor/leaflet/leaflet.css';
                document.head.appendChild(link);
            }

            // Lazy load Leaflet JS (UMD)
            if (!window.L) {
                await new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = '/js/vendor/leaflet/leaflet.js';
                    script.onload = resolve;
                    script.onerror = reject;
                    document.head.appendChild(script);
                });
            }
            const L = window.L;

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
                const marker = L.marker([m.lat, m.lng], { draggable: true })
                    .addTo(map.value)
                    .bindPopup(m.label);

                marker.on('dragend', (e) => {
                    const latlng = e.target.getLatLng();
                    updateMarkerPosition(m.id, latlng.lat, latlng.lng);

                    // Update local state to reflect new position in table
                    const localMarker = markers.value.find(x => x.id === m.id);
                    if (localMarker) {
                        localMarker.lat = latlng.lat;
                        localMarker.lng = latlng.lng;
                    }
                });
            });
        };

        const updateMarkerPosition = async (id, lat, lng) => {
            try {
                const res = await fetch(`/api/markers/${id}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ lat, lng })
                });

                if (!res.ok) {
                    console.error('Failed to update marker position');
                    // Optionally revert position here
                }
            } catch (e) {
                console.error('Error updating marker', e);
            }
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

                    // Ensure L is available (should be loaded by now)
                    const L = window.L;

                    if (L) {
                        L.marker([payload.lat, payload.lng], { draggable: true })
                            .addTo(map.value)
                            .bindPopup(payload.label)
                            .openPopup()
                            .on('dragend', (e) => {
                                // Assuming new marker ID is returned in result.id
                                updateMarkerPosition(result.id, e.target.getLatLng().lat, e.target.getLatLng().lng);
                                // Update local state
                                const localMarker = markers.value.find(x => x.id === result.id);
                                if (localMarker) {
                                    localMarker.lat = e.target.getLatLng().lat;
                                    localMarker.lng = e.target.getLatLng().lng;
                                }
                            });
                    }

                    // Add to reactive list so table updates
                    markers.value.push({
                        id: result.id,
                        ...payload
                    });

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

        const centerOnMarker = (marker) => {
            if (map.value && window.L) {
                map.value.flyTo([marker.lat, marker.lng], 16);
                window.L.popup()
                    .setLatLng([marker.lat, marker.lng])
                    .setContent(marker.label)
                    .openOn(map.value);
            }
        };

        onMounted(() => {
            // Wait for DOM
            nextTick(() => {
                initMap();
            });
        });

        return { showModal, newMarkerLabel, saveMarker, closeModal, labelInput, markers, centerOnMarker };
    }
};

