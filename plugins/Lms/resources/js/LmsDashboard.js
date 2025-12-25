import Icon from 'ui/Icon.js';
import UIButton from 'ui/Button.js';
import Card from 'ui/Card.js';
import Container from 'ui/Container.js';
import Row from 'ui/Row.js';
import Col from 'ui/Col.js';
import Spinner from 'ui/Spinner.js';
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
        'ui-spinner': Spinner,
        'ui-tag': Tag,
        'ui-title': UITitle,
        'ui-text': UIText
    },
    data() {
        return {
            courses: [],
            loading: true
        }
    },
    async mounted() {
        this.fetchCourses();
    },
    methods: {
        async fetchCourses() {
            this.loading = true;
            try {
                const res = await fetch('/api/lms/courses');
                if (res.ok) {
                    this.courses = await res.json();
                }
            } finally {
                this.loading = false;
            }
        },
        async createCourse() {
            const title = prompt("Course Title:");
            if (!title) return;

            await fetch('/api/lms/courses', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title, slug: title.toLowerCase().replace(/ /g, '-') })
            });
            this.fetchCourses();
            store.addNotification('Course created', 'success');
        }
    },
    template: `
        <ui-container class="p-4">
            <div class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <ui-title :level="1">LMS Dashboard</ui-title>
                <ui-button type="primary" @click="createCourse">
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
                        <ui-text size="small" class="text-muted" style="margin-bottom: 16px; display: block;">
                            Course ID: #{{ course.id }}
                        </ui-text>
                        <ui-text weight="bold" size="large" style="color: var(--accent-color);">
                            \${{ course.price }}
                        </ui-text>
                        <template #footer>
                            <ui-button size="small" style="width: 100%;">Manage Course</ui-button>
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
        </ui-container>
    `
}

