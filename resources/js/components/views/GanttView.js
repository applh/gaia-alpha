import { ref, computed } from 'vue';

export default {
    name: 'GanttView',
    props: {
        todos: Array
    },
    template: `
        <div class="gantt-view">
            <div class="gantt-controls">
                <button @click="shiftView(-7)" class="btn-small">&lt;&lt; Week</button>
                <div class="view-range">{{ startDateStr }} - {{ endDateStr }}</div>
                <button @click="shiftView(7)" class="btn-small">Week &gt;&gt;</button>
            </div>
            <div class="gantt-container">
                <div class="gantt-header-row">
                    <div class="gantt-col-label">Task</div>
                    <div class="gantt-timeline-header">
                        <div v-for="date in timelineDates" :key="date.ts" class="gantt-date-header">
                            {{ date.d }}/{{ date.m }}
                        </div>
                    </div>
                </div>
                <div class="gantt-body">
                    <div v-for="todo in sortedTodos" :key="todo.id" class="gantt-row">
                        <div class="gantt-col-label" :title="todo.title" :style="{ paddingLeft: (todo.level * 20 + 10) + 'px' }">
                            {{ todo.title }}
                        </div>
                        <div class="gantt-timeline-row">
                            <div 
                                v-if="hasDates(todo)"
                                class="gantt-bar"
                                :style="[getBarStyle(todo), { backgroundColor: todo.color || '' }]"
                                :class="{ 'completed': todo.completed }"
                                :title="todo.start_date + ' to ' + todo.end_date"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `,
    setup(props) {
        const viewStartDate = ref(new Date());
        // Align to start of week (Sunday)
        viewStartDate.value.setDate(viewStartDate.value.getDate() - viewStartDate.value.getDay());

        const daysToShow = 14; // 2 weeks view

        const timelineDates = computed(() => {
            const dates = [];
            const d = new Date(viewStartDate.value);
            for (let i = 0; i < daysToShow; i++) {
                dates.push({
                    ts: d.getTime(),
                    d: d.getDate(),
                    m: d.getMonth() + 1,
                    obj: new Date(d)
                });
                d.setDate(d.getDate() + 1);
            }
            return dates;
        });

        const startDateStr = computed(() => timelineDates.value[0].obj.toLocaleDateString());
        const endDateStr = computed(() => timelineDates.value[timelineDates.value.length - 1].obj.toLocaleDateString());

        // Flatten todos with level for hierarchy display
        const sortedTodos = computed(() => {
            const result = [];
            const process = (parentId, level) => {
                const children = props.todos
                    .filter(t => t.parent_id == parentId)
                    .sort((a, b) => (a.position - b.position) || (a.id - b.id));

                children.forEach(t => {
                    result.push({ ...t, level });
                    process(t.id, level + 1);
                });
            };
            process(null, 0);
            return result;
        });

        const hasDates = (todo) => todo.start_date || todo.end_date;

        const getBarStyle = (todo) => {
            if (!hasDates(todo)) return {};

            const start = todo.start_date ? new Date(todo.start_date) : new Date(todo.end_date);
            const end = todo.end_date ? new Date(todo.end_date) : new Date(todo.start_date);

            // Normalize to start of day
            start.setHours(0, 0, 0, 0);
            end.setHours(0, 0, 0, 0);

            const viewStart = timelineDates.value[0].obj;
            const viewEnd = timelineDates.value[timelineDates.value.length - 1].obj;

            // Check intersection
            if (end < viewStart || start > viewEnd) {
                return { display: 'none' };
            }

            // Calculate left and width %
            const rangeMs = viewEnd - viewStart + (24 * 3600 * 1000); // +1 day for inclusive
            const startOffset = Math.max(0, start - viewStart);
            const duration = Math.min(rangeMs - startOffset, end - Math.max(start, viewStart) + (24 * 3600 * 1000));
            // Actual duration of span:
            // visualStart = max(start, viewStart)
            // visualEnd = min(end, viewEnd)
            // visualDuration = visualEnd - visualStart

            // Easier: Unit is percentage of daysToShow
            // 1 day = 100 / daysToShow %

            const oneDayPct = 100 / daysToShow;

            // Calculate day difference from viewStart
            const daysFromStart = (start - viewStart) / (1000 * 3600 * 24);
            const durationDays = (end - start) / (1000 * 3600 * 24) + 1;

            return {
                left: (daysFromStart * oneDayPct) + '%',
                width: (durationDays * oneDayPct) + '%',
            };
        };

        const shiftView = (days) => {
            const d = new Date(viewStartDate.value);
            d.setDate(d.getDate() + days);
            viewStartDate.value = d;
        };

        return {
            timelineDates,
            startDateStr,
            endDateStr,
            sortedTodos,
            hasDates,
            getBarStyle,
            shiftView
        };
    }
}
