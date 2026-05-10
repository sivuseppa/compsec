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
    };
  },
  mounted() {
    store.fetchTasks();
  },
  template: `<div id="tasksPage">
              <add-task></add-task>
              <table class="card">
                <thead>
                  <tr>
                    <td>Task ID</td>
                    <td>Name</td>
                    <td>Description</td>
                    <td>Author ID</td>
                    <td>Status</td>
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
