import { store } from './store.js';

export default {
  props: {},
  data() {
    return {
      store,
      apiUrl: /backend/,
      newTask: { id: null, name: '', description: '', status: 'TODO', author_id: null },
      isOpen: false,
    };
  },
  methods: {
    async addTask() {
      const response = await fetch(this.apiUrl, {
        method: 'POST',
        body: JSON.stringify({ ...this.newTask, action: 'addTask' }),
      });
      const data = await response.json();
      store.notice.type = data?.status;
      store.notice.content = data?.message;

      store.fetchTasks();
    },
    toggleIsOpen() {
      this.isOpen = !this.isOpen;
    },
  },
  async mounted() {
    const currentAuthor = await this.store.getCurrentUser();
    this.newTask.author_id = currentAuthor.id;
  },
  template: `
            <div>
              <button @click="toggleIsOpen">{{ isOpen ? 'Close' : 'Add new task' }}</button>
              <form v-if="isOpen" @submit.prevent="addTask" id="saveTaskForm" class="saveUserForm card">
                <label>
                  <small>Name</small>
                  <input id="newTaskName" name="newTaskName" type="text" v-model="newTask.name" required />
                </label>
                <label>
                  <small>Description</small>
                  <input id="newTaskDescription" name="newTaskDescription" type="text" v-model="newTask.description" required />
                </label>
                <label>
                  <small>Status</small>
                  <select name="newTaskStatus" id="newTaskStatus" v-model="newTask.status">
                    <option value="TODO">TODO</option>
                    <option value="IN PROGRESS">IN PROGRESS</option>
                    <option value="IN REVIEW">IN REVIEW</option>
                    <option value="READY">READY</option>
                  </select>
                </label>
                <label>
                  <small>Author</small>
                  <input id="newAuthor" name="newAuthor" type="text" v-model="newTask.author_id" :readonly="this.store.currentUser.role !== 'admin'" required />
                </label>
                <button class="button">Add task</button>
              </form>
            </div>`,
};
