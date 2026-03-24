import Users from './users.js';
import Login from './login.js';
import Notice from './notice.js';

const fetchUserId = () => {
  const cookieValue = document.cookie
    .split('; ')
    .find((row) => row.startsWith('HSA_TOKEN='))
    ?.split('=')[1];
  const userId = cookieValue?.split('_')[0];
  return userId;
};

const pages = {
  1: {
    title: 'Dashboard',
    description: 'Dashboard content goes here.',
  },
  2: {
    title: 'Home Assistans',
    description: 'Home Assistans content goes here.',
  },
  3: {
    title: 'Users',
    description: 'Users content goes here.',
  },
  4: {
    title: 'Settings',
    description: 'Settings content goes here.',
  },
};

export default {
  components: {
    Users,
    Login,
    Notice,
  },
  data() {
    return {
      apiUrl: /backend/,
      isLoggedIn: false,
      page: pages[1],
    };
  },

  methods: {
    setIsloggedIn() {
      const cookieValue = document.cookie.split('; ').find((row) => row.startsWith('HSA_TOKEN='));
      this.isLoggedIn = cookieValue ? true : false;
    },

    async setPage(num) {
      this.page = pages[num];
    },

    async logout() {
      console.log('logout');
      const response = await fetch(this.apiUrl, {
        method: 'POST',
        body: JSON.stringify({ action: 'logout' }),
      });
      this.setIsloggedIn();
    },
  },
  mounted() {
    this.setIsloggedIn();
  },
};
