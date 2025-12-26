import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { api } from 'api';
import { store } from 'store';
import Icon from 'ui/Icon.js';
import UIButton from 'ui/Button.js';
import Card from 'ui/Card.js';
import Container from 'ui/Container.js';
import Row from 'ui/Row.js';
import Col from 'ui/Col.js';
import Input from 'ui/Input.js';
import Avatar from 'ui/Avatar.js';
import Tag from 'ui/Tag.js';
import { UITitle, UIText } from 'ui/Typography.js';

export default {
    components: {
        LucideIcon: Icon,
        'ui-button': UIButton,
        'ui-card': Card,
        'ui-container': Container,
        'ui-row': Row,
        'ui-col': Col,
        'ui-input': Input,
        'ui-avatar': Avatar,
        'ui-tag': Tag,
        'ui-title': UITitle,
        'ui-text': UIText
    },
    template: `
        <ui-container class="admin-page chat-page">
            <ui-row :gutter="20" class="chat-layout" style="height: calc(100vh - 180px); min-height: 500px;">
                <!-- Sidebar: User List -->
                <ui-col :xs="24" :md="8" :lg="6" style="height: 100%;">
                    <ui-card style="height: 100%; display: flex; flex-direction: column; padding: 0;">
                        <div class="chat-header" style="padding: 20px; border-bottom: 1px solid var(--border-color);">
                            <ui-title :level="3" style="margin: 0;">Messages</ui-title>
                        </div>
                        <div class="user-list" style="flex: 1; overflow-y: auto; padding: 10px;">
                            <div 
                                v-for="user in users" 
                                :key="user.id" 
                                class="user-item" 
                                :class="{ active: selectedUser && selectedUser.id === user.id }"
                                @click="selectUser(user)"
                                style="display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 8px; cursor: pointer; transition: all 0.2s; margin-bottom: 4px;"
                            >
                                <ui-avatar :name="user.username" size="sm" />
                                <div class="user-info-chat" style="flex: 1; display: flex; justify-content: space-between; align-items: center;">
                                    <ui-text weight="medium" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ user.username }}</ui-text>
                                    <ui-tag v-if="user.unread" type="danger" size="sm" effect="dark">{{ user.unread }}</ui-tag>
                                </div>
                            </div>
                        </div>
                    </ui-card>
                </ui-col>

                <!-- Main: Chat Window -->
                <ui-col :xs="24" :md="16" :lg="18" style="height: 100%;">
                    <ui-card style="height: 100%; display: flex; flex-direction: column; padding: 0;">
                        <template v-if="selectedUser">
                            <div class="chat-window-header" style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.02);">
                                <ui-avatar :name="selectedUser.username" size="sm" />
                                <ui-text weight="bold">{{ selectedUser.username }}</ui-text>
                            </div>
                            
                            <div class="messages-container" ref="messagesContainer" style="flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 12px;">
                                <div v-if="isLoading" style="display: flex; justify-content: center; padding: 20px;">
                                    <ui-text class="text-muted">Loading messages...</ui-text>
                                </div>
                                <div v-else-if="messages.length === 0" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    <LucideIcon name="message-circle" size="48" style="opacity: 0.1; margin-bottom: 12px;" />
                                    <p>No messages yet. Say hi!</p>
                                </div>
                                <div 
                                    v-for="msg in messages" 
                                    :key="msg.id" 
                                    class="message-bubble"
                                    :class="{ 
                                        'sent': msg.sender_id === currentUser.id,
                                        'received': msg.sender_id !== currentUser.id
                                    }"
                                    style="max-width: 80%; display: flex; flex-direction: column;"
                                    :style="msg.sender_id === currentUser.id ? 'align-self: flex-end; align-items: flex-end;' : 'align-self: flex-start; align-items: flex-start;'"
                                >
                                    <div 
                                        class="message-content" 
                                        style="padding: 10px 16px; border-radius: 18px; position: relative;"
                                        :style="msg.sender_id === currentUser.id 
                                            ? 'background: var(--accent-color); color: white; border-bottom-right-radius: 4px;' 
                                            : 'background: var(--card-bg-light); color: var(--text-color); border-bottom-left-radius: 4px;'
                                        "
                                    >
                                        {{ msg.content }}
                                    </div>
                                    <ui-text size="extra-small" class="text-muted" style="margin-top: 4px; padding: 0 4px;">{{ formatTime(msg.created_at) }}</ui-text>
                                </div>
                            </div>

                            <div class="chat-input-area" style="padding: 20px; border-top: 1px solid var(--border-color); display: flex; gap: 12px; align-items: flex-end;">
                                <ui-input 
                                    v-model="newMessage" 
                                    @keyup.enter="sendMessage"
                                    placeholder="Type a message..."
                                    :disabled="isSending"
                                    ref="inputField"
                                    style="flex: 1;"
                                />
                                <ui-button variant="primary" size="lg" @click="sendMessage" :disabled="!newMessage.trim() || isSending" style="width: 48px; border-radius: 50%; padding: 0;">
                                    <LucideIcon name="send" size="18" />
                                </ui-button>
                            </div>
                        </template>
                        
                        <div v-else style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; opacity: 0.3;">
                            <LucideIcon name="message-square" size="64" />
                            <ui-title :level="3" style="margin-top: 16px;">Select a user to start chatting</ui-title>
                        </div>
                    </ui-card>
                </ui-col>
            </ui-row>
        </ui-container>
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
                users.value = await api.get('chat/users');
            } catch (e) {
                console.error('Failed to fetch users', e);
            }
        };

        const fetchMessages = async () => {
            if (!selectedUser.value) return;
            try {
                const newData = await api.get(`chat/messages/${selectedUser.value.id}`);
                const initialLoad = messages.value.length === 0;
                const diff = newData.length !== messages.value.length;

                if (diff || (newData.length > 0 && messages.value.length > 0 && newData[newData.length - 1].id !== messages.value[messages.value.length - 1].id)) {
                    messages.value = newData;
                    if (initialLoad || diff) {
                        scrollToBottom();
                    }

                    if (selectedUser.value.unread > 0) {
                        markRead(selectedUser.value.id);
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

            if (user.unread > 0) {
                markRead(user.id);
            }
        };

        const markRead = async (senderId) => {
            try {
                await api.patch(`chat/read/${senderId}`);
                const u = users.value.find(u => u.id === senderId);
                if (u) u.unread = 0;
            } catch (e) { }
        };

        const sendMessage = async () => {
            if (!newMessage.value.trim() || !selectedUser.value) return;

            const content = newMessage.value;
            newMessage.value = '';
            isSending.value = true;

            try {
                const data = await api.post('chat', {
                    to: selectedUser.value.id,
                    content: content
                });

                messages.value.push({
                    id: data.id,
                    sender_id: currentUser.id,
                    receiver_id: selectedUser.value.id,
                    content: content,
                    created_at: data.created_at
                });
                scrollToBottom();
            } catch (e) {
                newMessage.value = content;
                store.addNotification('Error sending message: ' + e.message, 'error');
            } finally {
                isSending.value = false;
                inputField.value?.focus();
            }
        };

        const startPolling = () => {
            if (pollInterval) clearInterval(pollInterval);
            pollInterval = setInterval(async () => {
                await fetchMessages();
                await fetchUsers();
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

