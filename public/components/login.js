export default {
  props: ['modelValue'],
  emits: ['update:modelValue'],

  data() {
    return {
      apiUrl: /backend/,
      username: '',
      password: '',
    };
  },
  methods: {
    isloggedIn() {
      const cookieValue = document.cookie.split('; ').find((row) => row.startsWith('HSA_TOKEN='));
      return cookieValue ? true : false;
    },
    async login(e) {
      const response = await fetch(this.apiUrl, {
        method: 'POST',
        body: JSON.stringify({ username: this.username, password: this.password, action: 'login' }),
      });
      const data = await response.json();
      this.$emit('update:modelValue', this.isloggedIn());
      this.username = '';
      this.password = '';
    },
  },
  template: `<form @submit.prevent="login" id="loginform" class="loginform">
              <label>
                <small>Username</small>
                <input id="username" name="username" type="text" v-model="username" required />
              </label>
              <label>
                <small>Password</small>
                <input type="password" id="password" name="password" type="text" v-model="password" required />
              </label>
              <button class="button">Login</button>
            </form>`,
};
