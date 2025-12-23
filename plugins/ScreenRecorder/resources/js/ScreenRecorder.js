/**
 * Screen Recorder Plugin - Frontend Component
 * 
 * Handles screen capture, camera overlay (P-i-P), and recording.
 */

export default {
    template: `
        <div class="screen-recorder p-6 max-w-5xl mx-auto shadow-2xl rounded-3xl bg-slate-900 text-white mt-10 border border-slate-700">
            <div class="flex items-center justify-between mb-8 border-b border-slate-700 pb-4">
                <div class="flex items-center gap-3">
                    <div class="bg-indigo-600 p-2 rounded-xl shadow-lg shadow-indigo-500/20">
                        <i class="fas fa-video text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-white to-slate-400">Screen Recorder</h2>
                        <p class="text-xs text-slate-400 font-medium tracking-wide uppercase">Studio Quality Recording</p>
                    </div>
                </div>
                <div v-if="isRecording" class="flex items-center gap-2 px-3 py-1 bg-red-500/10 border border-red-500/20 rounded-full animate-pulse">
                    <div class="w-2 h-2 rounded-full bg-red-500"></div>
                    <span class="text-xs font-bold text-red-500 uppercase tracking-tighter">Recording: {{ formatTime(duration) }}</span>
                </div>
            </div>

            <!-- Preview Area -->
            <div class="relative bg-black rounded-2xl overflow-hidden aspect-video shadow-2xl border border-slate-800 group mb-8">
                <canvas ref="previewCanvas" class="w-full h-full object-contain"></canvas>
                
                <!-- Overlay Notifications -->
                <div v-if="!screenStream && !cameraStream" class="absolute inset-0 flex flex-col items-center justify-center text-slate-500 bg-slate-950/50 backdrop-blur-sm">
                    <i class="fas fa-desktop text-6xl mb-4 opacity-20"></i>
                    <p class="text-lg font-medium">Ready to start</p>
                    <p class="text-sm opacity-60">Initiate screen sharing to preview</p>
                </div>

                <!-- Controls Overlay (Hover) -->
                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-4 px-6 py-3 bg-slate-900/80 backdrop-blur-md rounded-2xl border border-white/10 opacity-0 group-hover:opacity-100 transition-all duration-300">
                     <button @click="toggleCamera" :class="cameraStream ? 'text-indigo-400' : 'text-slate-400'" class="hover:scale-110 transition-transform p-2">
                        <i :class="cameraStream ? 'fas fa-camera' : 'fas fa-camera-slash'"></i>
                    </button>
                    <button @click="toggleMic" :class="micEnabled ? 'text-indigo-400' : 'text-slate-400'" class="hover:scale-110 transition-transform p-2">
                        <i :class="micEnabled ? 'fas fa-microphone' : 'fas fa-microphone-slash'"></i>
                    </button>
                    <div class="w-px h-4 bg-slate-700 mx-2"></div>
                    <button v-if="!isRecording" @click="startRecording" :disabled="!screenStream" class="disabled:opacity-30 flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-1.5 rounded-lg text-sm font-bold transition-all">
                        <i class="fas fa-circle text-[10px]"></i> Record
                    </button>
                    <button v-else @click="stopRecording" class="flex items-center gap-2 bg-red-600 hover:bg-red-500 text-white px-4 py-1.5 rounded-lg text-sm font-bold animate-pulse">
                        <i class="fas fa-stop text-[10px]"></i> Stop
                    </button>
                </div>
            </div>

            <!-- Configuration & Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Source Setup -->
                <div class="bg-slate-800/40 p-5 rounded-2xl border border-slate-700/50">
                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-4">Input Sources</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-slate-800/60 rounded-xl border border-slate-700">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-desktop text-indigo-400"></i>
                                <span class="text-sm font-medium">Screen Share</span>
                            </div>
                            <button @click="setupScreen" class="text-xs bg-slate-700 hover:bg-slate-600 px-3 py-1.5 rounded-lg transition-colors font-bold">
                                {{ screenStream ? 'Change Source' : 'Select Screen' }}
                            </button>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-slate-800/60 rounded-xl border border-slate-700">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-camera text-indigo-400"></i>
                                <span class="text-sm font-medium">Camera Overlay (P-i-P)</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="cameraEnabled" @change="toggleCamera" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Last Recording / Quick Access -->
                <div class="bg-slate-800/40 p-5 rounded-2xl border border-slate-700/50 flex flex-col justify-center items-center text-center">
                    <div v-if="lastRecordingUrl" class="w-full">
                        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-4">Latest Captures</h3>
                        <div class="bg-slate-900 p-2 rounded-xl border border-slate-700 aspect-video relative overflow-hidden mb-3">
                            <video :src="lastRecordingUrl" controls class="w-full h-full object-cover"></video>
                        </div>
                        <button @click="saveToLibrary" :disabled="isSaving" class="w-full py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl font-bold shadow-lg shadow-emerald-500/10 transition-all text-sm disabled:opacity-50">
                            {{ isSaving ? 'Saving...' : 'Save to Media Library' }}
                        </button>
                    </div>
                    <div v-else class="opacity-30">
                        <i class="fas fa-history text-4xl mb-3"></i>
                        <p class="text-sm">No recent recordings in session</p>
                    </div>
                </div>
            </div>
        </div>
    `,
    data() {
        return {
            screenStream: null,
            cameraStream: null,
            audioStream: null,
            recorder: null,
            chunks: [],
            isRecording: false,
            cameraEnabled: false,
            micEnabled: true,
            duration: 0,
            timer: null,
            lastRecordingUrl: null,
            lastRecordingBlob: null,
            isSaving: false,
            pipPosition: { x: 20, y: 20, width: 320, height: 180 },
            canvasStream: null,
            renderLoop: null
        };
    },
    methods: {
        async setupScreen() {
            try {
                if (this.screenStream) {
                    this.screenStream.getTracks().forEach(t => t.stop());
                }
                this.screenStream = await navigator.mediaDevices.getDisplayMedia({
                    video: { cursor: "always" },
                    audio: true
                });

                this.screenStream.getTracks()[0].onended = () => {
                    this.screenStream = null;
                };

                this.initCanvasMixer();
            } catch (err) {
                console.error("Error sharing screen:", err);
                this.$toast.error("Failed to share screen. Check permissions.");
            }
        },

        async toggleCamera() {
            if (this.cameraEnabled) {
                try {
                    this.cameraStream = await navigator.mediaDevices.getUserMedia({
                        video: { width: 1280, height: 720 },
                        audio: false
                    });
                } catch (err) {
                    console.error("Error accessing camera:", err);
                    this.cameraEnabled = false;
                    this.$toast.error("Camera access denied.");
                }
            } else {
                if (this.cameraStream) {
                    this.cameraStream.getTracks().forEach(t => t.stop());
                }
                this.cameraStream = null;
            }
        },

        toggleMic() {
            this.micEnabled = !this.micEnabled;
            if (this.audioStream) {
                this.audioStream.getAudioTracks().forEach(t => t.enabled = this.micEnabled);
            }
        },

        initCanvasMixer() {
            const canvas = this.$refs.previewCanvas;
            const ctx = canvas.getContext('2d');

            const screenVideo = document.createElement('video');
            screenVideo.srcObject = this.screenStream;
            screenVideo.play();

            const cameraVideo = document.createElement('video');

            this.renderLoop = () => {
                if (!this.screenStream) return;

                // Sync canvas size to screen size
                const track = this.screenStream.getVideoTracks()[0];
                const settings = track.getSettings();
                if (canvas.width !== settings.width) {
                    canvas.width = settings.width || 1920;
                    canvas.height = settings.height || 1080;
                }

                // Draw Screen
                ctx.drawImage(screenVideo, 0, 0, canvas.width, canvas.height);

                // Draw Camera P-i-P if active
                if (this.cameraStream) {
                    if (cameraVideo.srcObject !== this.cameraStream) {
                        cameraVideo.srcObject = this.cameraStream;
                        cameraVideo.play();
                    }

                    const pWidth = canvas.width / 4;
                    const pHeight = pWidth * (9 / 16);
                    const pX = canvas.width - pWidth - 40;
                    const pY = canvas.height - pHeight - 40;

                    // Shadow/Border for PIP
                    ctx.save();
                    ctx.shadowColor = 'rgba(0,0,0,0.5)';
                    ctx.shadowBlur = 30;
                    ctx.fillStyle = '#000';
                    ctx.beginPath();
                    ctx.roundRect(pX - 2, pY - 2, pWidth + 4, pHeight + 4, 15);
                    ctx.fill();

                    // Draw Camera
                    ctx.beginPath();
                    ctx.roundRect(pX, pY, pWidth, pHeight, 15);
                    ctx.clip();
                    ctx.drawImage(cameraVideo, pX, pY, pWidth, pHeight);
                    ctx.restore();
                }

                requestAnimationFrame(this.renderLoop);
            };

            this.renderLoop();
        },

        async startRecording() {
            if (!this.screenStream) return;

            const canvas = this.$refs.previewCanvas;
            const stream = canvas.captureStream(30);

            // Add Audio
            if (this.micEnabled) {
                try {
                    this.audioStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    this.audioStream.getAudioTracks().forEach(track => stream.addTrack(track));
                } catch (e) {
                    console.warn("Could not add microphone audio:", e);
                }
            }

            this.chunks = [];
            this.recorder = new MediaRecorder(stream, { mimeType: 'video/webm;codecs=vp9,opus' });

            this.recorder.ondataavailable = (e) => {
                if (e.data.size > 0) this.chunks.push(e.data);
            };

            this.recorder.onstop = () => {
                const blob = new Blob(this.chunks, { type: 'video/webm' });
                this.lastRecordingBlob = blob;
                this.lastRecordingUrl = URL.createObjectURL(blob);
                this.isRecording = false;
                clearInterval(this.timer);
            };

            this.recorder.start();
            this.isRecording = true;
            this.duration = 0;
            this.timer = setInterval(() => this.duration++, 1000);

            this.lastRecordingUrl = null;
        },

        stopRecording() {
            if (this.recorder) {
                this.recorder.stop();
            }
            if (this.audioStream) {
                this.audioStream.getTracks().forEach(t => t.stop());
            }
        },

        async saveToLibrary() {
            if (!this.lastRecordingBlob) return;

            this.isSaving = true;
            const formData = new FormData();
            formData.append('video', this.lastRecordingBlob, 'recording.webm');
            formData.append('filename', 'recording_' + Date.now() + '.webm');

            try {
                const response = await fetch('/@/screen-recorder/upload', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    this.$toast.success("Successfully saved to Media Library!");
                    this.lastRecordingUrl = null;
                    this.lastRecordingBlob = null;
                } else {
                    throw new Error(result.error || "Unknown error");
                }
            } catch (err) {
                console.error("Upload failed:", err);
                this.$toast.error("Failed to save recording: " + err.message);
            } finally {
                this.isSaving = false;
            }
        },

        formatTime(seconds) {
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = seconds % 60;
            return [h, m, s].map(v => v < 10 ? "0" + v : v).filter((v, i) => v !== "00" || i > 0).join(":");
        }
    },
    beforeUnmount() {
        if (this.screenStream) this.screenStream.getTracks().forEach(t => t.stop());
        if (this.cameraStream) this.cameraStream.getTracks().forEach(t => t.stop());
        if (this.audioStream) this.audioStream.getTracks().forEach(t => t.stop());
        clearInterval(this.timer);
    }
};
