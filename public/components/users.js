import { store } from './store.js';
import User from './user.js';
import AddUser from './addUser.js';

export default {
  components: {
    User,
    AddUser,
  },
  props: {},
  data() {
    return {
      store,
      apiUrl: /backend/,
      users: [],
      newUser: { id: null, username: '', password: '', role: 'user' },
    };
  },
  methods: {
    async saveUser() {
      const response = await fetch(this.apiUrl, {
        method: 'POST',
        body: JSON.stringify({ ...this.newUser, action: 'saveUser' }),
      });
      const data = await response.json();
      store.notice.type = data?.status;
      store.notice.content = data?.message;
      store.fetchUsers();
    },
  },
  mounted() {
    store.fetchUsers();
    console.log(store);
  },
  template: `<div id="usersPage">
              <add-user></add-user>
              <table class="card">
                <thead>
                  <tr>
                    <td>ID</td>
                    <td>Username</td>
                    <td>Email</td>
                    <td>Role</td>
                    <td>New password</td>
                    <td></td>
                    <td></td>
                  </tr>
                </thead>
                <tbody>
                  <user v-for="user in store.users" :user="user" :key="user.id"></user>
                </tbody>
              </table>
            </div>`,
};
