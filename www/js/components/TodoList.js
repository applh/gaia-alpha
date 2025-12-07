import { ref, onMounted } from 'vue';

export default {
    template: `
        <div class="todo-container">
            <h2>My Todos</h2>
            <div class="add-todo">
                <input v-model="newTodo" @keyup.enter="addTodo" placeholder="Add new todo...">
                <button @click="addTodo">Add</button>
            </div>
            <ul class="todo-list">
                <li v-for="todo in todos" :key="todo.id" :class="{ completed: todo.completed }">
                    <span @click="toggleTodo(todo)">{{ todo.title }}</span>
                    <button @click="deleteTodo(todo.id)" class="delete-btn">x</button>
                </li>
            </ul>
        </div>
    `,
    setup() {
        const todos = ref([]);
        const newTodo = ref('');

        const fetchTodos = async () => {
            const res = await fetch('/api/todos');
            if (res.ok) todos.value = await res.json();
        };

        const addTodo = async () => {
            if (!newTodo.value.trim()) return;
            const res = await fetch('/api/todos', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title: newTodo.value })
            });
            if (res.ok) {
                const todo = await res.json();
                todos.value.unshift(todo);
                newTodo.value = '';
            }
        };

        const toggleTodo = async (todo) => {
            const updated = !todo.completed;
            const res = await fetch(`/api/todos/${todo.id}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ completed: updated })
            });
            if (res.ok) {
                todo.completed = updated ? 1 : 0;
            }
        };

        const deleteTodo = async (id) => {
            const res = await fetch(`/api/todos/${id}`, { method: 'DELETE' });
            if (res.ok) {
                todos.value = todos.value.filter(t => t.id !== id);
            }
        };

        onMounted(fetchTodos);

        return { todos, newTodo, addTodo, toggleTodo, deleteTodo };
    }
};
