import { store } from './store.js';
import Notice from './notice.js';

export default {
  components: {
    Notice,
  },
  data() {
    return {
      store,
      username: '',
      password: '',
      email: '',
      mode: 'login', // lostPassword, resetPassword
      pwResetKey: '',
    };
  },
  methods: {
    async send(e) {
      const response = await fetch(store.apiUrl, {
        method: 'POST',
        body: JSON.stringify({
          username: this.username,
          password: this.password,
          email: this.email,
          pw_reset_key: this.pwResetKey,
          action: this.mode,
        }),
      });
      const data = await response.json();
      console.log(data);
      await store.setIsloggedIn();
      if (store.isLoggedIn) {
        this.username = '';
        this.password = '';
        store.setNotice();
      } else {
        store.setNotice(data.status, data.message, false);
      }
    },
    setMode() {
      let params = new URLSearchParams(document.location.search);
      if (params.get('lostPassword') !== null) {
        this.mode = 'lostPassword';
      } else if (params.get('resetPassword') !== null) {
        this.mode = 'resetPassword';
        this.pwResetKey = params.get('resetPassword');
      } else {
        this.mode = 'login';
      }
    },
  },
  mounted() {
    this.setMode();
  },
  template: `<form @submit.prevent="send" id="loginform" class="loginform">
              <header class="logo">
                <svg xmlns="http://www.w3.org/2000/svg" height="40px" viewBox="0 -960 960 960" width="40px" fill="#cf2828de">
                  <path
                    d="M480-96q-135-34-223.5-154T168-516v-228l312-120 312 120v228q0 146-88 266T480-96Zm0-488L251-422q18 68 59 124.5t98 91.5v-178h144v178q57-35 98-91.5T709-422L480-584Zm0-203-240 92v194l240-171 240 171v-194l-240-92Z" />
                </svg>
                <h3>CompSecApp</h3>
              </header>

              <!-- Headings -->
              <h3 v-if="mode == 'login'">Login</h3>
              <div v-if="mode == 'lostPassword'">
                <h3>Lost you password?</h3>
                <small>Write your email address to recieve a password reset link.</small>
              </div>
              <div v-if="mode == 'resetPassword'">
                <h3>Reset password</h3>
                <small>Write your username and a new password.</small>
              </div>

              <notice></notice>

              <!-- Inputs -->
              <label v-if="mode !== 'lostPassword'">
                <small>Username</small>
                <input id="username" name="username" type="text" v-model="username" required />
              </label>
              <label v-if="mode == 'lostPassword'">
                <small>Email</small>
                <input id="email" name="email" type="email" v-model="email" required />
              </label>
              <label v-if="mode !== 'lostPassword'">
                <small>Password</small>
                <input id="password" name="password" type="password" v-model="password" required />
              </label>

              <!-- Buttons -->
              <button v-if="mode == 'login'" class="button">Login</button>
              <button v-if="mode == 'lostPassword'" class="button">Send email</button>
              <button v-if="mode == 'resetPassword'" class="button">Reset password</button>
              
              <a v-if="mode !== 'login'" href="/"><small><- Back to login<small></a>
              <a v-if="mode == 'login'" href="/?lostPassword"><small>Lost your password?<small></a>
            </form>`,
};
