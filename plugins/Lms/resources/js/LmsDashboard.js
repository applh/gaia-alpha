import Icon from 'ui/Icon.js';
import UIButton from 'ui/Button.js';
import Card from 'ui/Card.js';
import { ref, onMounted } from 'vue';
import { api } from 'api';
import { store } from 'store';
import Container from 'ui/Container.js';
import Row from 'ui/Row.js';
import Col from 'ui/Col.js';
import Spinner from 'ui/Spinner.js';
import Tag from 'ui/Tag.js';
import { UITitle, UIText } from 'ui/Typography.js';
import Modal from 'ui/Modal.js';
import Input from 'ui/Input.js';

export default {
    components: {
        LucideIcon: Icon,
        'ui-button': UIButton,
        'ui-card': Card,
        'ui-container': Container,
        'ui-row': Row,
        'ui-col': Col,
        'ui-spinner': Spinner,
        'ui-tag': Tag,
        'ui-title': UITitle,
        'ui-text': UIText,
        'ui-modal': Modal,
        'ui-input': Input
    },
    data() {
        return {
            courses: [],
            loading: true,
            showCreateModal: false,
            newCourseTitle: '',
            creating: false
        }
    },
    async mounted() {
        this.loadCourses();
    },
    methods: {
        async loadCourses() {
            this.loading = true;
            try {
                this.courses = await api.get('lms/courses');
            } catch (err) {
                console.error('Failed to load courses', err);
            } finally {
                this.loading = false;
            }
        },
        openCreateModal() {
            this.newCourseTitle = '';
            this.showCreateModal = true;
        },
        async submitCreateCourse() {
            if (!this.newCourseTitle) return;

            this.creating = true;
            try {
                await api.post('lms/courses', {
                    title: this.newCourseTitle,
                    slug: this.newCourseTitle.toLowerCase().replace(/ /g, '-')
                });
                this.showCreateModal = false;
                await this.loadCourses();
                store.addNotification('Course created', 'success');
            } catch (err) {
                store.addNotification('Error creating course: ' + err.message, 'error');
            } finally {
                this.creating = false;
            }
        }
    },
    template: `
        <ui-container class="p-4">
            <div class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <ui-title :level="1">LMS Dashboard</ui-title>
                <ui-button variant="primary" @click="openCreateModal">
                    <LucideIcon name="plus" size="18" style="margin-right: 8px;" />
                    New Course
                </ui-button>
            </div>
            
            <div v-if="loading" style="display: flex; justify-content: center; padding: 48px;">
                <ui-spinner size="lg" />
            </div>
            
            <ui-row v-else :gutter="20">
                <ui-col v-for="course in courses" :key="course.id" :xs="24" :sm="12" :md="8" style="margin-bottom: 24px;">
                    <ui-card style="height: 100%;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                            <ui-title :level="3" style="margin: 0;">{{ course.title }}</ui-title>
                            <ui-tag :type="course.status === 'published' ? 'success' : 'info'">{{ course.status }}</ui-tag>
                        </div>
                        <ui-text size="sm" class="text-muted" style="margin-bottom: 16px; display: block;">
                            Course ID: #{{ course.id }}
                        </ui-text>
                        <ui-text weight="bold" size="lg" style="color: var(--accent-color);">
                            \${{ course.price }}
                        </ui-text>
                        <template #footer>
                            <ui-button size="sm" style="width: 100%;">Manage Course</ui-button>
                        </template>
                    </ui-card>
                </ui-col>
                <ui-col v-if="courses.length === 0" :span="24">
                    <ui-card style="text-align: center; padding: 48px;">
                        <LucideIcon name="book-open" size="48" style="margin-bottom: 16px; opacity: 0.2;" />
                        <ui-text class="text-muted">No courses found. Start by creating your first one!</ui-text>
                    </ui-card>
                </ui-col>
            </ui-row>

            <ui-modal :show="showCreateModal" title="Create New Course" @close="showCreateModal = false">
                <ui-input
                    v-model="newCourseTitle"
                    label="Course Title"
                    placeholder="Enter course title"
                    required
                    :disabled="creating"
                    @keyup.enter="submitCreateCourse"
                />
                <template #footer>
                    <ui-button variant="secondary" @click="showCreateModal = false" style="margin-right: 10px;" :disabled="creating">Cancel</ui-button>
                    <ui-button variant="primary" @click="submitCreateCourse" :disabled="!newCourseTitle || creating">
                        <span v-if="creating">Creating...</span>
                        <span v-else>Create Course</span>
                    </ui-button>
                </template>
            </ui-modal>
        </ui-container>
    `
}

