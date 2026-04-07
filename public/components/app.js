import { store } from './store.js';
import Users from './users.js';
import Tasks from './tasks.js';
import Login from './login.js';
import Notice from './notice.js';
import Avatar from './avatar.js';
import Navigation from './navigation.js';
import Settings from './settings.js';

const fetchUserId = () => {
  const cookieValue = document.cookie
    .split('; ')
    .find((row) => row.startsWith('HSA_TOKEN='))
    ?.split('=')[1];
  const userId = cookieValue?.split('_')[0];
  return userId;
};

export default {
  components: {
    Users,
    Tasks,
    Login,
    Notice,
    Avatar,
    Navigation,
    Settings,
  },
  data() {
    return {
      store,
    };
  },

  methods: {
    async logout() {
      console.log('logout');
      const response = await fetch(store.apiUrl, {
        method: 'POST',
        body: JSON.stringify({ action: 'logout' }),
      });
      store.setIsloggedIn();
      store.setNotice();
    },
  },
  mounted() {
    store.setIsloggedIn();
    store.setPage();
  },
};
