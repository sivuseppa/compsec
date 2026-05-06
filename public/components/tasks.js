import { store } from './store.js';
import Task from './task.js';
import AddTask from './addTask.js';

export default {
  components: {
    Task,
    AddTask,
  },
  props: {},
  data() {
    return {
      store,
      apiUrl: /backend/,
      tasks: [],
      newTask: { id: null, name: '', description: '', status: 'TODO' },
    };
  },
  methods: {
    async save() {
      const response = await fetch(this.apiUrl, {
        method: 'POST',
        body: JSON.stringify({ ...this.newTask, action: 'saveTask' }),
      });
      const data = await response.json();
      store.notice.type = data?.status;
      store.notice.content = data?.message;
      store.fetchTasks();
    },
  },
  mounted() {
    store.fetchTasks();
    console.log(store);
  },
  template: `<div id="tasksPage">
              <add-task></add-task>
              <table class="card">
                <thead>
                  <tr>
                    <td>Task ID</td>
                    <td>Name</td>
                    <td>Description</td>
                    <td>Status</td>
                    <td>Author ID</td>
                    <td></td>
                    <td></td>
                  </tr>
                </thead>
                <tbody>
                  <task v-for="task in store.tasks" :task="task" :key="task.id"></task>
                </tbody>
              </table>
            </div>`,
};
