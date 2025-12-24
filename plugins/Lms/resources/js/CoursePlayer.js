export default {
    props: ['courseId'],
    data() {
        return {
            course: null,
            currentLesson: null,
            loading: true
        }
    },
    async mounted() {
        const res = await fetch(`/api/lms/courses/${this.courseId}`);
        this.course = await res.json();

        if (this.course.modules && this.course.modules.length > 0) {
            // Select first lesson
            if (this.course.modules[0].lessons && this.course.modules[0].lessons.length > 0) {
                this.currentLesson = this.course.modules[0].lessons[0];
            }
        }
        this.loading = false;
    },
    methods: {
        selectLesson(lesson) {
            this.currentLesson = lesson;
        }
    },
    template: `
        <div class="flex h-screen">
            <div v-if="loading" class="p-4">Loading Course...</div>
            
            <template v-else>
                <!-- Sidebar -->
                <div class="w-1/4 bg-gray-100 p-4 overflow-y-auto">
                    <h2 class="font-bold mb-4">{{ course.title }}</h2>
                    <div v-for="module in course.modules" :key="module.id" class="mb-4">
                        <h3 class="font-semibold text-gray-700 mb-2">{{ module.title }}</h3>
                        <ul>
                            <li v-for="lesson in module.lessons" :key="lesson.id" 
                                class="cursor-pointer p-2 rounded hover:bg-gray-200"
                                :class="{'bg-blue-100': currentLesson && currentLesson.id === lesson.id}"
                                @click="selectLesson(lesson)">
                                {{ lesson.title }}
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Content -->
                <div class="w-3/4 p-8">
                    <div v-if="currentLesson">
                        <h1 class="text-3xl font-bold mb-4">{{ currentLesson.title }}</h1>
                        <div class="prose max-w-none">
                            <div v-html="currentLesson.content"></div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    `
}
