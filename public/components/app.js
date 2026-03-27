import { store } from './store.js';
import Users from './users.js';
import Login from './login.js';
import Notice from './notice.js';
import Avatar from './avatar.js';
import Navigation from './navigation.js';

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
    Login,
    Notice,
    Avatar,
    Navigation,
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
    },
  },
  mounted() {
    store.setIsloggedIn();
    store.setPage();
  },
};
