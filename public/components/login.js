import { store } from './store.js';
import Notice from './notice.js';

export default {
  // props: ['modelValue'],
  // emits: ['update:modelValue'],
  components: {
    Notice,
  },
  data() {
    return {
      store,
      username: '',
      password: '',
    };
  },
  methods: {
    async login(e) {
      const response = await fetch(store.apiUrl, {
        method: 'POST',
        body: JSON.stringify({ username: this.username, password: this.password, action: 'login' }),
      });
      const data = await response.json();
      await store.setIsloggedIn();
      if (store.isLoggedIn) {
        this.username = '';
        this.password = '';
        store.setNotice();
      } else {
        store.setNotice('error', `Check your credentials and try again.`, false);
      }
    },
  },
  template: `<form @submit.prevent="login" id="loginform" class="loginform">
              <div class="logo">
                <svg xmlns="http://www.w3.org/2000/svg" height="40px" viewBox="0 -960 960 960" width="40px" fill="#cf2828de">
                  <path
                    d="M480-96q-135-34-223.5-154T168-516v-228l312-120 312 120v228q0 146-88 266T480-96Zm0-488L251-422q18 68 59 124.5t98 91.5v-178h144v178q57-35 98-91.5T709-422L480-584Zm0-203-240 92v194l240-171 240 171v-194l-240-92Z" />
                </svg>
                <h3>CompSecApp</h3>
              </div>
              <notice></notice>
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
