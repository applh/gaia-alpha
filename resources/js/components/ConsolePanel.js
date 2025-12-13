import { ref, onMounted, nextTick } from 'vue';
import Icon from './Icon.js';

export default {
    components: { LucideIcon: Icon },
    template: `
        <div class="panel-container console-panel" style="height: calc(100vh - 80px); display: flex; flex-direction: column; background: #1e1e1e; color: #0f0; font-family: monospace;">
            <div class="panel-header" style="background: #333; padding: 10px 20px; border-bottom: 1px solid #444; flex-shrink: 0;">
                <h2 style="margin:0; font-size: 1.2rem; color: #fff; display: flex; align-items: center; gap: 10px;">
                    <LucideIcon name="terminal" size="20"></LucideIcon>
                    Console / CLI Runner
                </h2>
            </div>
            
            <div class="console-output" ref="outputContainer" style="flex-grow: 1; overflow-y: auto; padding: 20px; white-space: pre-wrap;">
                <div v-for="(line, index) in history" :key="index" :class="line.type">
                    <span v-if="line.type === 'command'" style="color: #fff; font-weight: bold;">$ {{ line.content }}</span>
                    <span v-else-if="line.type === 'response'" style="color: #ccc;">{{ line.content }}</span>
                    <span v-else-if="line.type === 'error'" style="color: #f55;">{{ line.content }}</span>
                    <span v-else-if="line.type === 'system'" style="color: #888; font-style: italic;">{{ line.content }}</span>
                </div>
            </div>

            <div class="console-input-area" style="padding: 10px 20px; background: #252525; border-top: 1px solid #444; flex-shrink: 0; display: flex; gap: 10px; align-items: center;">
                <span style="color: #0f0; font-weight: bold;">$</span>
                <input 
                    ref="inputField"
                    v-model="currentCommand" 
                    @keydown.enter="executeCommand"
                    @keydown.up.prevent="navigateHistory(-1)"
                    @keydown.down.prevent="navigateHistory(1)"
                    type="text" 
                    style="flex-grow: 1; background: transparent; border: none; color: #fff; font-family: monospace; font-size: 1rem; outline: none;"
                    placeholder="Type command... (e.g., db:list users, help)"
                    :disabled="isProcessing"
                >
                <button v-if="isProcessing" disabled style="background: transparent; border: none; color: #888;">Running...</button>
            </div>
        </div>
    `,
    setup() {
        const currentCommand = ref('');
        const history = ref([]);
        const commandHistory = ref([]); // For up/down arrow navigation
        const historyIndex = ref(-1);
        const isProcessing = ref(false);
        const outputContainer = ref(null);
        const inputField = ref(null);

        onMounted(() => {
            history.value.push({ type: 'system', content: 'Gaia Alpha Web Console v1.0. Type "help" for commands.' });
            inputField.value.focus();
        });

        const scrollToBottom = async () => {
            await nextTick();
            if (outputContainer.value) {
                outputContainer.value.scrollTop = outputContainer.value.scrollHeight;
            }
        };

        const executeCommand = async () => {
            const cmd = currentCommand.value.trim();
            if (!cmd) return;

            // Add to display history
            history.value.push({ type: 'command', content: cmd });
            
            // Add to navigation history
            commandHistory.value.push(cmd);
            historyIndex.value = -1; // Reset nav index
            
            currentCommand.value = '';
            isProcessing.value = true;
            await scrollToBottom();

            try {
                // Client-side clear command
                if (cmd === 'clear' || cmd === 'cls') {
                    history.value = [];
                    isProcessing.value = false;
                    return;
                }

                const res = await fetch('/api/console/run', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ command: cmd })
                });

                if (res.ok) {
                    const data = await res.json();
                    
                    if (data.status === 0) {
                        history.value.push({ type: 'response', content: data.output || '(No output)' });
                    } else {
                        history.value.push({ type: 'error', content: `Exit Code ${data.status}:\n` + data.output });
                    }
                } else {
                    const err = await res.json();
                    history.value.push({ type: 'error', content: 'Error: ' + (err.error || res.statusText) });
                }
            } catch (e) {
                history.value.push({ type: 'error', content: 'Connection Error: ' + e.message });
            } finally {
                isProcessing.value = false;
                await scrollToBottom();
                inputField.value.focus();
            }
        };

        const navigateHistory = (direction) => {
            if (commandHistory.value.length === 0) return;

            if (historyIndex.value === -1) {
                historyIndex.value = commandHistory.value.length;
            }

            historyIndex.value += direction;

            if (historyIndex.value < 0) {
                historyIndex.value = 0;
            } else if (historyIndex.value > commandHistory.value.length) {
                historyIndex.value = commandHistory.value.length;
                currentCommand.value = '';
                return;
            }

            if (historyIndex.value < commandHistory.value.length) {
                currentCommand.value = commandHistory.value[historyIndex.value];
            } else {
                currentCommand.value = '';
            }
        };

        return {
            currentCommand,
            history,
            isProcessing,
            outputContainer,
            inputField,
            executeCommand,
            navigateHistory
        };
    }
};
