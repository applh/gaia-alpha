import { ref, reactive, onMounted, computed } from 'vue';
import { api } from 'api';
import { store } from 'store';
import Icon from 'ui/Icon.js';
import UIButton from 'ui/Button.js';
import Card from 'ui/Card.js';
import Textarea from 'ui/Textarea.js';
import Avatar from 'ui/Avatar.js';
import { UITitle, UIText } from 'ui/Typography.js';
import Divider from 'ui/Divider.js';

const STYLES = `
.comments-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}
.comment-item-wrapper {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.replies-list {
    margin-left: 3rem;
    padding-left: 1.5rem;
    border-left: 2px solid var(--border-color);
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-top: 1rem;
}
.rating-select {
    display: flex;
    gap: 0.25rem;
    cursor: pointer;
}
.star {
    color: #ffd700;
}
.star.empty {
    color: var(--border-color);
    opacity: 0.3;
}
`;

export default {
    props: {
        targetType: { type: String, required: true },
        targetId: { type: [String, Number], required: true },
        allowRating: { type: Boolean, default: false },
        title: { type: String, default: 'Comments' }
    },
    components: {
        LucideIcon: Icon,
        'ui-button': UIButton,
        'ui-card': Card,
        'ui-textarea': Textarea,
        'ui-avatar': Avatar,
        'ui-title': UITitle,
        'ui-text': UIText,
        'ui-divider': Divider
    },
    template: `
        <div class="comments-section">
            <div class="style-injector" v-html="'<style>' + styles + '</style>'"></div>
            
            <div class="comments-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <ui-title :level="3" style="margin: 0;">{{ title }} ({{ totalComments }})</ui-title>
            </div>

            <!-- New Comment Form -->
            <ui-card style="margin-bottom: 32px;">
                <ui-textarea 
                    v-model="newComment.content" 
                    placeholder="Write a comment..."
                    style="margin-bottom: 16px;"
                />
                
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <!-- Rating Input -->
                    <div v-if="allowRating" class="rating-select">
                        <LucideIcon 
                            v-for="i in 5" 
                            :key="i" 
                            name="star" 
                            size="20" 
                            class="star" 
                            :class="{ empty: i > newComment.rating }"
                            @click="setRating(i)"
                        />
                    </div>
                    <div v-else></div>
                    
                    <ui-button variant="primary" @click="submitComment" :disabled="submitting || !newComment.content" :loading="submitting">
                        Post Comment
                    </ui-button>
                </div>
            </ui-card>

            <!-- Comments List -->
            <div class="comments-list">
                <div v-if="loading" style="text-align: center; padding: 24px;">
                    <ui-text class="text-muted">Loading comments...</ui-text>
                </div>
                <div v-else-if="comments.length === 0" style="text-align: center; padding: 48px; border: 1px dashed var(--border-color); border-radius: 12px;">
                    <LucideIcon name="message-square" size="40" style="opacity: 0.2; margin-bottom: 12px;" />
                    <ui-text class="text-muted">No comments yet. Be the first!</ui-text>
                </div>
                
                <div v-for="comment in comments" :key="comment.id" class="comment-item-wrapper">
                    <ui-card style="padding: 16px;">
                        <div style="display: flex; gap: 16px;">
                            <ui-avatar :name="comment.author_name || comment.user_username || 'Guest'" size="sm" />
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <div>
                                        <ui-text weight="bold" style="margin-right: 8px;">{{ comment.author_name || comment.user_username || 'Guest' }}</ui-text>
                                        <span v-if="comment.rating" style="display: inline-flex; gap: 2px; vertical-align: middle;">
                                            <LucideIcon name="star" size="12" style="color: #ffd700;" v-for="r in comment.rating" :key="r" />
                                        </span>
                                    </div>
                                    <ui-text size="extra-small" class="text-muted">{{ formatDate(comment.created_at) }}</ui-text>
                                </div>
                                <ui-text style="display: block; margin-bottom: 12px; white-space: pre-line;">{{ comment.content }}</ui-text>
                                <ui-button size="sm" @click="toggleReply(comment.id)">
                                    <LucideIcon name="reply" size="14" style="margin-right: 4px;" />
                                    Reply
                                </ui-button>
                                
                                <!-- Reply Form -->
                                <div v-if="replyingTo === comment.id" style="margin-top: 16px;">
                                    <ui-textarea v-model="replyContent" placeholder="Write a reply..." style="margin-bottom: 12px;" />
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <ui-button size="sm" @click="replyingTo = null">Cancel</ui-button>
                                        <ui-button size="sm" variant="primary" @click="submitReply(comment.id)">Post Reply</ui-button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </ui-card>

                    <!-- Nested Replies -->
                    <div v-if="comment.replies && comment.replies.length > 0" class="replies-list">
                        <ui-card v-for="reply in comment.replies" :key="reply.id" style="padding: 12px 16px; background: rgba(255,255,255,0.02);">
                            <div style="display: flex; gap: 12px;">
                                <ui-avatar :name="reply.author_name || reply.user_username || 'Guest'" size="sm" />
                                <div style="flex: 1;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                        <ui-text weight="bold" size="sm">{{ reply.author_name || reply.user_username || 'Guest' }}</ui-text>
                                        <ui-text size="extra-small" class="text-muted">{{ formatDate(reply.created_at) }}</ui-text>
                                    </div>
                                    <ui-text size="sm" style="display: block; white-space: pre-line;">{{ reply.content }}</ui-text>
                                </div>
                            </div>
                        </ui-card>
                    </div>
                </div>
            </div>
        </div>
    `,
    setup(props) {
        const styles = STYLES;
        const comments = ref([]);
        const loading = ref(true);
        const submitting = ref(false);
        const replyingTo = ref(null);
        const replyContent = ref('');

        const newComment = reactive({
            content: '',
            rating: 0
        });

        const totalComments = computed(() => {
            let count = comments.value.length;
            comments.value.forEach(c => {
                if (c.replies) count += c.replies.length;
            });
            return count;
        });

        const fetchComments = async () => {
            loading.value = true;
            try {
                const data = await api.get(`comments?type=${props.targetType}&id=${props.targetId}`);
                if (data) {
                    comments.value = data;
                }
            } catch (e) {
                console.error("Failed to load comments", e);
            } finally {
                loading.value = false;
            }
        };

        const submitComment = async () => {
            if (!newComment.content) return;
            submitting.value = true;

            try {
                const data = await api.post('comments', {
                    commentable_type: props.targetType,
                    commentable_id: props.targetId,
                    content: newComment.content,
                    rating: props.allowRating ? newComment.rating : null
                });

                if (data) {
                    newComment.content = '';
                    newComment.rating = 0;
                    await fetchComments();
                    store.addNotification('Comment posted', 'success');
                }
            } catch (e) {
                store.addNotification('Error posting comment: ' + e.message, 'error');
            } finally {
                submitting.value = false;
            }
        };

        const toggleReply = (id) => {
            if (replyingTo.value === id) {
                replyingTo.value = null;
            } else {
                replyingTo.value = id;
                replyContent.value = '';
            }
        };

        const submitReply = async (parentId) => {
            if (!replyContent.value) return;

            try {
                await api.post('comments', {
                    commentable_type: props.targetType,
                    commentable_id: props.targetId,
                    parent_id: parentId,
                    content: replyContent.value
                });

                replyingTo.value = null;
                replyContent.value = '';
                await fetchComments();
                store.addNotification('Reply posted', 'success');
            } catch (e) {
                store.addNotification('Error posting reply: ' + e.message, 'error');
            }
        };

        const setRating = (r) => {
            if (props.allowRating) {
                newComment.rating = r;
            }
        };

        const formatDate = (dateStr) => {
            return new Date(dateStr).toLocaleDateString() + ' ' + new Date(dateStr).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        };

        onMounted(() => {
            fetchComments();
        });

        return {
            styles,
            comments,
            loading,
            submitting,
            newComment,
            replyingTo,
            replyContent,
            totalComments,
            submitComment,
            toggleReply,
            submitReply,
            setRating,
            formatDate
        };
    }
}

