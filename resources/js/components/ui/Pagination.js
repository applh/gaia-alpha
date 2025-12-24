export default {
    props: {
        total: {
            type: Number,
            required: true
        },
        pageSize: {
            type: Number,
            default: 10
        },
        currentPage: {
            type: Number,
            default: 1
        }
    },
    emits: ['update:currentPage', 'current-change'],
    computed: {
        pageCount() {
            return Math.ceil(this.total / this.pageSize);
        },
        pages() {
            const pages = [];
            const count = this.pageCount;
            const current = this.currentPage;

            if (count <= 7) {
                for (let i = 1; i <= count; i++) pages.push(i);
            } else {
                pages.push(1);
                if (current > 4) pages.push('...');

                const start = Math.max(2, current - 2);
                const end = Math.min(count - 1, current + 2);

                for (let i = start; i <= end; i++) pages.push(i);

                if (current < count - 3) pages.push('...');
                pages.push(count);
            }
            return pages;
        }
    },
    methods: {
        setPage(page) {
            if (page === '...' || page === this.currentPage) return;
            if (page < 1 || page > this.pageCount) return;
            this.$emit('update:currentPage', page);
            this.$emit('current-change', page);
        }
    },
    template: `
        <div class="pagination" v-if="pageCount > 1">
            <button 
                class="pagination-btn" 
                :disabled="currentPage === 1"
                @click="setPage(currentPage - 1)"
            >&lt;</button>
            
            <button 
                v-for="page in pages" 
                :key="page"
                class="pagination-btn"
                :class="{ active: currentPage === page, 'is-dots': page === '...' }"
                @click="setPage(page)"
            >
                {{ page }}
            </button>
            
            <button 
                class="pagination-btn" 
                :disabled="currentPage === pageCount"
                @click="setPage(currentPage + 1)"
            >&gt;</button>
        </div>
    `
};
