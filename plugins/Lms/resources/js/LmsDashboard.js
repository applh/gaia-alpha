export default {
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
            const res = await fetch('/api/lms/courses');
            this.courses = await res.json();
            this.loading = false;
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
        }
    },
    template: `
        <div class="p-4">
            <div class="flex justify-between mb-4">
                <h1 class="text-2xl font-bold">LMS Dashboard</h1>
                <button @click="createCourse" class="bg-blue-500 text-white px-4 py-2 rounded">New Course</button>
            </div>
            
            <div v-if="loading">Loading...</div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div v-for="course in courses" :key="course.id" class="border p-4 rounded shadow">
                    <h2 class="font-bold text-lg">{{ course.title }}</h2>
                    <p class="text-gray-600">{{ course.status }}</p>
                    <p class="mt-2 font-mono">\${{ course.price }}</p>
                </div>
            </div>
        </div>
    `
}
