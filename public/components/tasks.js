import { store } from './store.js';
import Task from './task.js';
// import AddUser from './addUser.js';

export default {
  components: {
    Task,
    // AddUser,
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
              <!-- <add-user></add-user> -->
              <table class="card">
                <thead>
                  <tr>
                    <td>ID</td>
                    <td>Name</td>
                    <td>Description</td>
                    <td>Status</td>
                    <td>Author</td>
                    <td></td>
                    <td></td>
                  </tr>
                </thead>
                <tbody>
                  <task v-for="task in store.tasks" :user="task" :key="user.id"></task>
                </tbody>
              </table>
            </div>`,
};
