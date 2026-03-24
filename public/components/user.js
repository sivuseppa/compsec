import { store } from './store.js';

export default {
  props: {
    user: Object,
  },
  data() {
    return { store, apiUrl: /backend/ };
  },
  methods: {
    async deleteUser(userId) {
      console.log(userId);
      const response = await fetch(this.apiUrl, {
        method: 'POST',
        body: JSON.stringify({ id: userId, action: 'deleteUser' }),
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
              <td>{{ user.role }}</td>
              <td><input :id="'newPass-' + user.id" name="userNewPassword" type="text" /></td>
              <td><button @click.prevent="editUser(user.id)">Save</button></td>
              <td><button @click.prevent="deleteUser(user.id)">X</button></td>
            </tr>`,
};
