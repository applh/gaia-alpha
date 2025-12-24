import { ref, reactive, onMounted, computed } from 'vue';
import Icon from 'ui/Icon.js'; // Assuming this exists based on NodeEditor

const STYLES = `
.comments-section {
    font-family: var(--font-stack, sans-serif);
    margin-top: 2rem;
    padding: 1rem;
    background: var(--bg-secondary, #f9fafb);
    border-radius: 8px;
    border: 1px solid var(--border-color, #e5e7eb);
}

.comments-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.comments-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary, #111827);
}

.comment-form {
    margin-bottom: 2rem;
    background: var(--bg-primary, #ffffff);
    padding: 1rem;
    border-radius: 6px;
    border: 1px solid var(--border-color, #e5e7eb);
}

.comment-input {
    width: 100%;
    min-height: 80px;
    padding: 0.75rem;
    border: 1px solid var(--border-color, #d1d5db);
    border-radius: 4px;
    margin-bottom: 0.5rem;
    font-family: inherit;
    resize: vertical;
}

.comment-actions {
    display: flex;
    justify-content: flex-end; /* Right align buttons */
    align-items: center;
    gap: 1rem;
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
    color: #d1d5db;
}

.btn-submit {
    background: var(--primary-color, #2563eb);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    font-weight: 500;
}

.btn-submit:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.comments-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.comment-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: var(--bg-primary, #ffffff);
    border-radius: 6px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.comment-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: #6b7280;
    flex-shrink: 0;
}

.comment-content {
    flex: 1;
}

.comment-meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    color: var(--text-muted, #6b7280);
}

.comment-author {
    font-weight: 600;
    color: var(--text-primary, #111827);
    margin-right: 0.5rem;
}

.comment-body {
    color: var(--text-primary, #374151);
    line-height: 1.5;
    white-space: pre-line;
}

.comment-reply-btn {
    margin-top: 0.5rem;
    background: none;
    border: none;
    color: var(--text-muted, #6b7280);
    cursor: pointer;
    font-size: 0.875rem;
    padding: 0;
}

.comment-reply-btn:hover {
    color: var(--primary-color, #2563eb);
}

.replies-list {
    margin-top: 1rem;
    margin-left: 2rem; /* Indent replies */
    border-left: 2px solid var(--border-color, #e5e7eb);
    padding-left: 1rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
`;

export default {
    props: {
        targetType: { type: String, required: true },
        targetId: { type: [String, Number], required: true },
        allowRating: { type: Boolean, default: false },
        title: { type: String, default: 'Comments' }
    },
    components: { Icon },
    template: `
        <div class="comments-section">
            <div class="style-injector" v-html="'<style>' + styles + '</style>'"></div>
            
            <div class="comments-header">
                <h3 class="comments-title">{{ title }} ({{ totalComments }})</h3>
            </div>

            <!-- New Comment Form -->
            <div class="comment-form">
                <textarea 
                    v-model="newComment.content" 
                    class="comment-input" 
                    placeholder="Write a comment..."
                ></textarea>
                
                <div class="comment-actions">
                    <!-- Rating Input -->
                    <div v-if="allowRating" class="rating-select">
                        <Icon 
                            v-for="i in 5" 
                            :key="i" 
                            name="star" 
                            size="20" 
                            class="star" 
                            :class="{ empty: i > newComment.rating }"
                            @click="setRating(i)"
                        />
                    </div>
                    
                    <button class="btn-submit" @click="submitComment" :disabled="submitting || !newComment.content">
                        {{ submitting ? 'Posting...' : 'Post Comment' }}
                    </button>
                </div>
            </div>

            <!-- Comments List -->
            <div class="comments-list">
                <div v-if="loading" class="text-center text-muted">Loading comments...</div>
                <div v-else-if="comments.length === 0" class="text-center text-muted">No comments yet. Be the first!</div>
                
                <div v-for="comment in comments" :key="comment.id" class="comment-item-wrapper">
                    <!-- Comment Component (Recursive via Render or just nested logic) -->
                    <!-- Simplification: Just rendering top level and one level deep for now, or building a recursive component -->
                    <div class="comment-item">
                        <div class="comment-avatar">
                            {{ getInitials(comment.author_name || comment.user_username || 'Guest') }}
                        </div>
                        <div class="comment-content">
                            <div class="comment-meta">
                                <div>
                                    <span class="comment-author">{{ comment.author_name || comment.user_username || 'Guest' }}</span>
                                    <span v-if="comment.rating" class="comment-rating">
                                        <Icon name="star" size="12" fill="#ffd700" color="#ffd700" v-for="r in comment.rating" :key="r" style="display:inline-block"/>
                                    </span>
                                </div>
                                <span class="comment-date">{{ formatDate(comment.created_at) }}</span>
                            </div>
                            <div class="comment-body">{{ comment.content }}</div>
                            <button class="comment-reply-btn" @click="toggleReply(comment.id)">Reply</button>
                            
                            <!-- Reply Form -->
                            <div v-if="replyingTo === comment.id" class="comment-form mt-2">
                                <textarea v-model="replyContent" class="comment-input" placeholder="Write a reply..."></textarea>
                                <div class="comment-actions">
                                    <button class="btn-submit" @click="submitReply(comment.id)">Reply</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nested Replies -->
                    <div v-if="comment.replies && comment.replies.length > 0" class="replies-list">
                        <div v-for="reply in comment.replies" :key="reply.id" class="comment-item">
                            <div class="comment-avatar">
                                {{ getInitials(reply.author_name || reply.user_username || 'Guest') }}
                            </div>
                            <div class="comment-content">
                                <div class="comment-meta">
                                    <span class="comment-author">{{ reply.author_name || reply.user_username || 'Guest' }}</span>
                                    <span class="comment-date">{{ formatDate(reply.created_at) }}</span>
                                </div>
                                <div class="comment-body">{{ reply.content }}</div>
                            </div>
                        </div>
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
                const res = await fetch(`/@/api/comments?type=${props.targetType}&id=${props.targetId}`);
                const data = await res.json();
                if (data.data) {
                    comments.value = data.data;
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
                const res = await fetch('/@/api/comments', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        commentable_type: props.targetType,
                        commentable_id: props.targetId,
                        content: newComment.content,
                        rating: props.allowRating ? newComment.rating : null
                    })
                });

                if (res.ok) {
                    newComment.content = '';
                    newComment.rating = 0;
                    await fetchComments();
                }
            } catch (e) {
                alert('Error posting comment');
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
                const res = await fetch('/@/api/comments', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        commentable_type: props.targetType,
                        commentable_id: props.targetId,
                        parent_id: parentId,
                        content: replyContent.value
                    })
                });

                if (res.ok) {
                    replyingTo.value = null;
                    replyContent.value = '';
                    await fetchComments();
                }
            } catch (e) {
                alert('Error posting reply');
            }
        };

        const setRating = (r) => {
            if (props.allowRating) {
                newComment.rating = r;
            }
        };

        const getInitials = (name) => {
            return name ? name.substring(0, 2).toUpperCase() : '??';
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
            getInitials,
            formatDate
        };
    }
}
