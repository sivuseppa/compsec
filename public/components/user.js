import { store } from './store.js';

export default {
  props: {
    user: Object,
  },
  data() {
    return { store, apiUrl: /backend/, newRole: '', newPassword: '' };
  },
  methods: {
    async saveUser() {
      const response = await fetch(this.apiUrl, {
        method: 'POST',
        body: JSON.stringify({
          ...this.user,
          password: this.newPassword,
          role: this.user.role,
          action: 'saveUser',
        }),
      });
      const data = await response.json();
      store.notice.type = data?.status;
      store.notice.content = data?.message;

      store.fetchUsers();
    },
    async deleteUser(userId) {
      console.log(userId);
      const response = await fetch(this.apiUrl, {
        method: 'POST',
        body: JSON.stringify({ id: this.user.id, action: 'deleteUser' }),
      });
      const data = await response.json();
      store.notice.type = data?.status;
      store.notice.content = data?.message;
      store.fetchUsers();
    },
    async editUser() {},
  },
  template: `<tr>
              <td>{{ user.id }}</td>
              <td>{{ user.username }}</td>
              <td>{{ user.email }}</td>
              <!-- <td>{{ user.role }}</td> -->
              <td>
                <select name="newUserRole" value="" v-model="user.role">
                  <option value="user">User</option>
                  <option value="admin">Admin</option>
                </select>
              </td>
              <td><input  name="userNewPassword" type="text" v-model="newPassword" /></td>
              <td><button @click.prevent="saveUser">Save</button></td>
              <td><button @click.prevent="deleteUser" class="delete rounded-icon">
                <svg xmlns="http://www.w3.org/2000/svg" height="16px" viewBox="0 -960 960 960" width="16px" fill="#ffffff"><path d="M280-120q-33 0-56.5-23.5T200-200v-520h-40v-80h200v-40h240v40h200v80h-40v520q0 33-23.5 56.5T680-120H280Zm400-600H280v520h400v-520ZM360-280h80v-360h-80v360Zm160 0h80v-360h-80v360ZM280-720v520-520Z"/></svg>
              </button></td>
            </tr>`,
};
