import { store } from './store.js';
// import User from './user.js';

export default {
  props: {},
  data() {
    return {
      store,
      apiUrl: /backend/,
      newUser: { id: null, email: '', username: '', password: '', role: 'user' },
      isOpen: false,
    };
  },
  methods: {
    async addUser() {
      const response = await fetch(this.apiUrl, {
        method: 'POST',
        body: JSON.stringify({ ...this.newUser, action: 'addUser' }),
      });
      const data = await response.json();
      store.notice.type = data?.status;
      store.notice.content = data?.message;

      store.fetchUsers();
    },
    toggleIsOpen() {
      this.isOpen = !this.isOpen;
    },
  },
  template: `
            <div>
              <button @click="toggleIsOpen">{{ isOpen ? 'Close' : 'Add new user' }}</button>
              <form v-if="isOpen" @submit.prevent="addUser" id="saveUserForm" class="saveUserForm card">
                <label>
                  <small>Email</small>
                  <input id="newuUserEmail" name="newuUserEmail" type="email" v-model="newUser.email" />
                </label>
                <label>
                  <small>Username</small>
                  <input id="newuUsername" name="newuUsername" type="text" v-model="newUser.username" required />
                </label>
                <label>
                  <small>User role</small>
                  <select name="newUserRole" id="newUserRole" v-model="newUser.role">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                  </select>
                </label>
                <label>
                  <small>Password</small>
                  <input id="newPassword" name="newPassword" type="text" v-model="newUser.password" required />
                </label>
                <button class="button">Add user</button>
              </form>
            </div>`,
};
