
import { ref, onMounted, nextTick } from 'vue';
import { useSorting } from '../composables/useSorting.js';
import SortTh from './SortTh.js';
import Icon from './Icon.js';

export default {
    components: { SortTh, LucideIcon: Icon },
    template: `
    <div class="admin-page map-page" style="height: calc(100vh - 80px); display: flex; flex-direction: column;">
            
            <div class="admin-header">
                <div style="display:flex; align-items:center; gap:20px;">
                    <h2 class="page-title" style="display: flex; align-items: center;">
                        <span style="display: inline-flex; margin-right: 12px;">
                            <LucideIcon name="map" size="32"></LucideIcon>
                        </span>
                        Maps
                    </h2>
                    <div class="button-group">
                        <button @click="setViewMode('2d')" class="btn" :class="{ 'btn-primary': viewMode === '2d', 'btn-secondary': viewMode !== '2d' }">2D Map</button>
                        <button @click="setViewMode('3d')" class="btn" :class="{ 'btn-primary': viewMode === '3d', 'btn-secondary': viewMode !== '3d' }">3D Globe</button>
                    </div>
                </div>
            </div>

            <div class="map-layout">
                <div class="map-viewport" ref="mapContainer">
                    <div v-show="viewMode === '2d'" id="leaflet-map" style="width: 100%; height: 100%; z-index: 1;"></div>
                    <div v-show="viewMode === '3d'" id="globe-container" style="width: 100%; height: 100%; z-index: 1; background: #000;"></div>
                </div>
                
                <div class="map-sidebar admin-card">
                    <div class="card-header">
                        <h3>Markers</h3>
                        <div class="text-small text-muted" v-if="markers.length">{{ markers.length }} markers</div>
                    </div>
                    
                    <div class="table-container" style="flex: 1; overflow-y: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <SortTh name="id" :current-sort="sortColumn" :sort-dir="sortDirection" @sort="sortBy">ID</SortTh>
                                    <SortTh name="label" :current-sort="sortColumn" :sort-dir="sortDirection" @sort="sortBy">Label</SortTh>
                                    <SortTh name="lat" :current-sort="sortColumn" :sort-dir="sortDirection" @sort="sortBy">Lat</SortTh>
                                    <SortTh name="lng" :current-sort="sortColumn" :sort-dir="sortDirection" @sort="sortBy">Lng</SortTh>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="marker in sortedMarkers" :key="marker.id">
                                    <td>{{ marker.id }}</td>
                                    <td>{{ marker.label }}</td>
                                    <td>{{ marker.lat.toFixed(2) }}</td>
                                    <td>{{ marker.lng.toFixed(2) }}</td>
                                    <td class="text-right">
                                        <button @click="centerOnMarker(marker)" class="btn btn-sm btn-secondary btn-icon" title="View">
                                            <LucideIcon name="eye" size="14" />
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="markers.length === 0">
                                    <td colspan="5" class="text-center text-muted" style="padding: 32px;">
                                        No markers yet. Click map to add.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal for new marker -->
            <div v-if="showModal" class="modal-overlay" style="z-index: 1000;">
                <div class="modal-content">
                    <h3>New Marker</h3>
                    <div class="form-group">
                        <label>Label</label>
                        <input v-model="newMarkerLabel" placeholder="Enter label" @keyup.enter="saveMarker" ref="labelInput" />
                    </div>
                    <div class="form-actions" style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button @click="closeModal" class="btn btn-secondary">Cancel</button>
                        <button @click="saveMarker" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </div>
    </div>
    `,
    setup() {
        const map = ref(null);
        const globe = ref(null);
        const markers = ref([]);
        const { sortColumn, sortDirection, sortBy, sortedData: sortedMarkers } = useSorting(markers, 'id', 'desc');
        const showModal = ref(false);
        const newMarkerLabel = ref('');
        const newMarkerPos = ref(null);
        const labelInput = ref(null);
        const viewMode = ref('2d'); // '2d' or '3d'
        const globeInitialized = ref(false);

        const initMap = async () => {
            if (map.value) return;

            // Lazy load CSS
            if (!document.getElementById('leaflet-css')) {
                const link = document.createElement('link');
                link.id = 'leaflet-css';
                link.rel = 'stylesheet';
                link.href = '/min/css/vendor/leaflet/leaflet.css';
                document.head.appendChild(link);
            }

            // Lazy load Leaflet JS (UMD)
            if (!window.L) {
                await new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = '/min/js/vendor/leaflet/leaflet.js';
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

        const mapContainer = ref(null);

        const initGlobe = async () => {
            if (globeInitialized.value) return;

            // Lazy load Globe.gl
            if (!window.Globe) {
                await new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = '/min/js/vendor/globe.gl.js';
                    script.onload = resolve;
                    script.onerror = reject;
                    document.head.appendChild(script);
                });
            }

            const Globe = window.Globe;
            const container = document.getElementById('globe-container');
            const { clientWidth: width, clientHeight: height } = mapContainer.value;

            globe.value = Globe()(container)
                .width(width)
                .height(height)
                .globeImageUrl('https://unpkg.com/three-globe/example/img/earth-blue-marble.jpg')
                .bumpImageUrl('https://unpkg.com/three-globe/example/img/earth-topology.png')
                .backgroundImageUrl('https://unpkg.com/three-globe/example/img/night-sky.png')
                .pointsData(markers.value)
                .pointLat('lat')
                .pointLng('lng')
                .pointLabel('label')
                .pointAltitude(0.1) // make points float a bit
                .pointRadius(0.5)
                .pointColor(() => 'red');

            // Adjust controls
            globe.value.controls().autoRotate = true;
            globe.value.controls().autoRotateSpeed = 0.5;

            globeInitialized.value = true;

            // Handle Resize
            window.addEventListener('resize', handleResize);
        };

        const handleResize = () => {
            if (globe.value && mapContainer.value) {
                const { clientWidth: width, clientHeight: height } = mapContainer.value;
                globe.value.width(width);
                globe.value.height(height);
            }
            if (map.value) {
                map.value.invalidateSize();
            }
        };

        const setViewMode = (mode) => {
            viewMode.value = mode;
            if (mode === '3d') {
                nextTick(() => {
                    initGlobe();
                    handleResize(); // Ensure size is correct upon switching
                });
            } else {
                // Resize map when coming back to view
                nextTick(() => {
                    if (map.value) map.value.invalidateSize();
                });
            }
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
                const res = await fetch('/@/markers');
                if (res.ok) {
                    const data = await res.json();
                    markers.value = data;
                    renderMarkers(L);
                    if (globe.value) {
                        globe.value.pointsData(markers.value);
                    }
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

            const featureGroup = L.featureGroup();

            markers.value.forEach(m => {
                const marker = L.marker([m.lat, m.lng], { draggable: true })
                    .addTo(map.value)
                    .bindPopup(m.label);

                featureGroup.addLayer(marker);

                marker.on('dragend', (e) => {
                    const latlng = e.target.getLatLng();
                    updateMarkerPosition(m.id, latlng.lat, latlng.lng);

                    // Update local state to reflect new position in table
                    const localMarker = markers.value.find(x => x.id === m.id);
                    if (localMarker) {
                        localMarker.lat = latlng.lat;
                        localMarker.lng = latlng.lng;
                    }
                    if (globe.value) globe.value.pointsData(markers.value);
                });
            });

            if (markers.value.length > 0) {
                map.value.fitBounds(featureGroup.getBounds().pad(0.1));
            }
        };

        const updateMarkerPosition = async (id, lat, lng) => {
            try {
                const res = await fetch(`/@/markers/${id}`, {
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
                const res = await fetch('/@/markers', {
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
                                if (globe.value) globe.value.pointsData(markers.value);
                            });
                    }

                    // Add to reactive list so table updates
                    markers.value.push({
                        id: result.id,
                        ...payload
                    });

                    if (globe.value) globe.value.pointsData(markers.value);

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
            if (viewMode.value === '2d') {
                if (map.value && window.L) {
                    map.value.flyTo([marker.lat, marker.lng], 16);
                    window.L.popup()
                        .setLatLng([marker.lat, marker.lng])
                        .setContent(marker.label)
                        .openOn(map.value);
                }
            } else {
                // 3D Globe centering
                if (globe.value) {
                    globe.value.pointOfView({ lat: marker.lat, lng: marker.lng, altitude: 2 }, 2000); // Animate to view
                }
            }
        };

        onMounted(() => {
            // Wait for DOM
            nextTick(() => {
                initMap();
            });
        });

        return {
            showModal, newMarkerLabel, saveMarker, closeModal, labelInput, markers, centerOnMarker, viewMode, setViewMode,
            sortColumn, sortDirection, sortBy, sortedMarkers, mapContainer
        };
    }
};

