import { ref, computed } from 'vue';

export default {
    name: 'CalendarView',
    props: {
        todos: Array
    },
    template: `
        <div class="calendar-view">
            <div class="calendar-header-controls">
                <button @click="prevMonth" class="btn-small">&lt;</button>
                <div class="month-title">{{ monthName }} {{ currentYear }}</div>
                <button @click="nextMonth" class="btn-small">&gt;</button>
            </div>
            <div class="calendar-grid">
                <div class="calendar-day-header" v-for="day in dayNames" :key="day">{{ day }}</div>
                <div 
                    v-for="cell in calendarCells" 
                    :key="cell.date" 
                    class="calendar-cell"
                    :class="{ 'other-month': !cell.isCurrentMonth, 'today': cell.isToday }"
                >
                    <div class="cell-date">{{ cell.day }}</div>
                    <div class="cell-content">
                        <div 
                            v-for="todo in cell.todos" 
                            :key="todo.id" 
                            class="calendar-todo-item"
                            :class="{ 'completed': todo.completed }"
                            :title="todo.title"
                            :style="{ backgroundColor: todo.color || '' }"
                        >
                            {{ todo.title }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `,
    setup(props) {
        const currentDate = ref(new Date());

        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        const monthName = computed(() => {
            return currentDate.value.toLocaleString('default', { month: 'long' });
        });

        const currentYear = computed(() => {
            return currentDate.value.getFullYear();
        });

        const calendarCells = computed(() => {
            const year = currentDate.value.getFullYear();
            const month = currentDate.value.getMonth();

            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);

            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - startDate.getDay());

            const endDate = new Date(lastDay);
            endDate.setDate(endDate.getDate() + (6 - endDate.getDay()));

            const cells = [];

            // Create a new date object to avoid modifying startDate in place loop
            const iterDate = new Date(startDate);

            while (iterDate <= endDate) {
                const dateStr = iterDate.toISOString().split('T')[0];
                const dayTodos = props.todos.filter(t => {
                    if (!t.start_date && !t.end_date) return false;
                    const start = t.start_date ? t.start_date : t.end_date;
                    const end = t.end_date ? t.end_date : t.start_date;
                    return dateStr >= start && dateStr <= end;
                });

                cells.push({
                    date: dateStr,
                    day: iterDate.getDate(),
                    isCurrentMonth: iterDate.getMonth() === month,
                    isToday: new Date().toISOString().split('T')[0] === dateStr,
                    todos: dayTodos
                });

                iterDate.setDate(iterDate.getDate() + 1);
            }
            return cells;
        });

        const prevMonth = () => {
            currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1);
        };

        const nextMonth = () => {
            currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1);
        };

        return {
            monthName,
            currentYear,
            dayNames,
            calendarCells,
            prevMonth,
            nextMonth
        };
    }
}
