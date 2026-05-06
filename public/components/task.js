import { store } from './store.js';

export default {
  props: {
    task: Object,
  },
  data() {
    return { store };
  },
  methods: {
    async saveTask() {
      const response = await fetch(store.apiUrl, {
        method: 'POST',
        body: JSON.stringify({ ...this.task, action: 'saveTask' }),
      });
      const data = await response.json();
      store.notice.type = data?.status;
      store.notice.content = data?.message;
      store.fetchTasks();
    },
    async deleteTask() {
      const response = await fetch(store.apiUrl, {
        method: 'POST',
        body: JSON.stringify({ id: this.task.id, action: 'deleteTask' }),
      });
      const data = await response.json();
      store.notice.type = data?.status;
      store.notice.content = data?.message;
      store.fetchTasks();
    },
    async edit() {},
  },
  template: `<tr>
              <td>{{ task.id }}</td>
              <td>{{ task.name }}</td>
              <td>{{ task.description }}</td>
              <td>{{ task.status }}</td>
              <td>{{ task.author_id }}</td>
              
              <td><button @click.prevent="deleteTask" class="delete rounded-icon">
                <svg xmlns="http://www.w3.org/2000/svg" height="16px" viewBox="0 -960 960 960" width="16px" fill="#ffffff"><path d="M280-120q-33 0-56.5-23.5T200-200v-520h-40v-80h200v-40h240v40h200v80h-40v520q0 33-23.5 56.5T680-120H280Zm400-600H280v520h400v-520ZM360-280h80v-360h-80v360Zm160 0h80v-360h-80v360ZM280-720v520-520Z"/></svg>
              </button></td>
            </tr>`,
};
