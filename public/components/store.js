import { reactive } from 'https://unpkg.com/vue@3/dist/vue.esm-browser.js';
export const store = reactive({
  apiUrl: /backend/,
  currentUser: {},
  isLoggedIn: false,
  users: [],
  notice: {
    type: '',
    content: '',
  },
  pages: {
    1: {
      title: 'Dashboard',
      description: 'One day, here you have a dashboard.',
    },
    2: {
      title: 'Tasks',
      description: 'Here you can manage tasks.',
    },
    3: {
      title: 'Users',
      description: 'Here you can manage users.',
    },
    4: {
      title: 'Settings',
      description: 'Here you can manage settings.',
    },
  },
  currentPage: {},

  async getCurrentUser() {
    const response = await fetch(this.apiUrl + '?action=getCurrentUser');
    const data = await response.json();
    this.currentUser = { ...data.message };
  },
  async setIsloggedIn() {
    const cookieValue = document.cookie.split('; ').find((row) => row.startsWith('HSA_TOKEN='));
    this.isLoggedIn = cookieValue ? true : false;
    if (this.isLoggedIn) {
      await this.getCurrentUser();
      console.log(this.currentUser);
    }
  },
  async fetchUsers() {
    const response = await fetch(this.apiUrl + '?action=getUsers');
    const data = await response.json();
    this.users = [...data.message];
  },
  setPage(num = null) {
    if (num) {
      localStorage.setItem('currentPage', num);
    } else if (localStorage.getItem('currentPage')) {
      num = localStorage.getItem('currentPage');
    } else {
      num = 1;
    }
    this.currentPage = this.pages[num];
  },
});
