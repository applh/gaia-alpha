import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { store } from '../store.js';
import Icon from './Icon.js';

export default {
    components: { LucideIcon: Icon },
    template: `
        <div class="admin-page chat-page">
            <div class="chat-layout">
                <!-- Sidebar: User List -->
                <div class="chat-sidebar admin-card">
                    <div class="chat-header">
                        <h3>Messages</h3>
                    </div>
                    <div class="user-list">
                        <div 
                            v-for="user in users" 
                            :key="user.id" 
                            class="user-item" 
                            :class="{ active: selectedUser && selectedUser.id === user.id }"
                            @click="selectUser(user)"
                        >
                            <div class="user-avatar">
                                <LucideIcon name="user" size="20" />
                            </div>
                            <div class="user-info-chat">
                                <span class="username">{{ user.username }}</span>
                                <span v-if="user.unread" class="unread-badge">{{ user.unread }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main: Chat Window -->
                <div class="chat-window admin-card">
                    <template v-if="selectedUser">
                        <div class="chat-window-header">
                            <div class="header-user">
                                <LucideIcon name="user" size="18" />
                                <span>{{ selectedUser.username }}</span>
                            </div>
                        </div>
                        
                        <div class="messages-container" ref="messagesContainer">
                            <div v-if="isLoading" class="loading">Loading...</div>
                            <div v-else-if="messages.length === 0" class="empty-state">
                                No messages yet. Say hi!
                            </div>
                            <div 
                                v-for="msg in messages" 
                                :key="msg.id" 
                                class="message-bubble"
                                :class="{ 
                                    'sent': msg.sender_id === currentUser.id,
                                    'received': msg.sender_id !== currentUser.id
                                }"
                            >
                                <div class="message-content">{{ msg.content }}</div>
                                <div class="message-time">{{ formatTime(msg.created_at) }}</div>
                            </div>
                        </div>

                        <div class="chat-input-area">
                            <input 
                                v-model="newMessage" 
                                @keyup.enter="sendMessage"
                                placeholder="Type a message..."
                                :disabled="isSending"
                                ref="inputField"
                            >
                            <button @click="sendMessage" class="btn-primary btn-icon" :disabled="!newMessage.trim() || isSending">
                                <LucideIcon name="send" size="18" />
                            </button>
                        </div>
                    </template>
                    
                    <div v-else class="empty-selection">
                        <LucideIcon name="message-square" size="48" class="text-muted" />
                        <p>Select a user to start chatting</p>
                    </div>
                </div>
            </div>
        </div>
    `,
    setup() {
        const users = ref([]);
        const messages = ref([]);
        const selectedUser = ref(null);
        const newMessage = ref('');
        const messagesContainer = ref(null);
        const inputField = ref(null);
        const isLoading = ref(false);
        const isSending = ref(false);
        const currentUser = store.state.user;
        let pollInterval = null;

        const fetchUsers = async () => {
            try {
                const res = await fetch('/@/chat/users');
                if (res.ok) {
                    users.value = await res.json();
                }
            } catch (e) {
                console.error('Failed to fetch users', e);
            }
        };

        const fetchMessages = async () => {
            if (!selectedUser.value) return;
            try {
                // Don't set isLoading on poll, only initial
                const res = await fetch(`/@/chat/messages/${selectedUser.value.id}`);
                if (res.ok) {
                    const newData = await res.json();

                    // Only update if length changed to avoid jitter, or better, smart merge?
                    // For now, simple replace is okay but might impact scroll if we are not at bottom.
                    // Let's replace and see.
                    const initialLoad = messages.value.length === 0;
                    const diff = newData.length !== messages.value.length;

                    if (diff || (newData.length > 0 && messages.value.length > 0 && newData[newData.length - 1].id !== messages.value[messages.value.length - 1].id)) {
                        messages.value = newData;
                        if (initialLoad || diff) {
                            scrollToBottom();
                        }

                        // If we have messages from them in the new batch, mark read?
                        // Simple logic: If window is open, mark unread messages as read
                        if (selectedUser.value.unread > 0) {
                            markRead(selectedUser.value.id);
                        }
                    }
                }
            } catch (e) { }
        };

        const selectUser = async (user) => {
            selectedUser.value = user;
            messages.value = [];
            isLoading.value = true;
            await fetchMessages();
            isLoading.value = false;
            startPolling();
            setTimeout(() => inputField.value?.focus(), 100);

            // Mark read immediately upon selection
            if (user.unread > 0) {
                markRead(user.id);
            }
        };

        const markRead = async (senderId) => {
            await fetch(`/@/chat/read/${senderId}`, { method: 'PATCH' });
            // Update local badge
            const u = users.value.find(u => u.id === senderId);
            if (u) u.unread = 0;
        };

        const sendMessage = async () => {
            if (!newMessage.value.trim() || !selectedUser.value) return;

            const content = newMessage.value;
            newMessage.value = ''; // optimistic clear
            isSending.value = true;

            try {
                const res = await fetch('/@/chat', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        to: selectedUser.value.id,
                        content: content
                    })
                });

                if (res.ok) {
                    const data = await res.json();
                    // Optimistic append
                    messages.value.push({
                        id: data.id,
                        sender_id: currentUser.id,
                        receiver_id: selectedUser.value.id,
                        content: content,
                        created_at: data.created_at
                    });
                    scrollToBottom();
                } else {
                    newMessage.value = content; // restore
                    alert('Failed to send');
                }
            } catch (e) {
                newMessage.value = content;
                alert('Error sending');
            } finally {
                isSending.value = false;
                inputField.value?.focus();
            }
        };

        const startPolling = () => {
            if (pollInterval) clearInterval(pollInterval);
            pollInterval = setInterval(async () => {
                await fetchMessages(); // poll active chat
                await fetchUsers(); // poll user list (for unread badges)
            }, 3000);
        };

        const scrollToBottom = () => {
            nextTick(() => {
                if (messagesContainer.value) {
                    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
                }
            });
        };

        const formatTime = (ts) => {
            if (!ts) return '';
            const date = new Date(ts);
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        };

        onMounted(() => {
            fetchUsers();
            // We can also poll users list even if no chat selected?
            pollInterval = setInterval(fetchUsers, 5000);
        });

        onUnmounted(() => {
            if (pollInterval) clearInterval(pollInterval);
        });

        return {
            users,
            messages,
            selectedUser,
            newMessage,
            messagesContainer,
            inputField,
            selectUser,
            sendMessage,
            currentUser,
            formatTime,
            isLoading,
            isSending
        };
    }
};
