import { reactive } from 'https://unpkg.com/vue@3/dist/vue.esm-browser.js';
export const store = reactive({
  apiUrl: /backend/,
  users: [],
  notice: {
    type: '',
    content: '',
  },
  async fetchUsers() {
    const response = await fetch(this.apiUrl + '?action=getUsers');
    const data = await response.json();
    this.users = [...data.message];
  },
});
