import { reactive } from 'https://unpkg.com/vue@3/dist/vue.esm-browser.js';
export const store = reactive({
  apiUrl: /backend/,
  currentUser: {},
  isLoggedIn: false,
  users: [],
  tasks: [],
  notice: {
    type: '',
    content: '',
    canBeDissmissed: true,
  },
  pages: {
    0: {
      title: 'Login',
      description: '',
    },
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
  settings: [],
  currentPage: {},

  async getCurrentUser() {
    if (Object.keys(this.currentUser).length > 0) {
      return this.currentUser;
    }
    const response = await fetch(this.apiUrl + '?action=getCurrentUser');
    const data = await response.json();
    this.currentUser = { ...data.message };
    return this.currentUser;
  },
  async setIsloggedIn() {
    const response = await fetch(this.apiUrl + '?action=getCurrentUser');
    const data = await response.json();
    this.currentUser = { ...data.message };
    this.isLoggedIn = this.currentUser?.id ? true : false;
  },
  setNotice(type = '', content = '', canBeDissmissed = true) {
    this.notice.type = type;
    this.notice.content = content;
    this.notice.canBeDissmissed = canBeDissmissed;
  },
  async fetchUsers() {
    const response = await fetch(this.apiUrl + '?action=getUsers');
    const data = await response.json();
    this.users = [...data.message];
  },
  async fetchSettings() {
    const response = await fetch(this.apiUrl + '?action=getSettings');
    const data = await response.json();
    this.settings = [...data.message];
  },
  async fetchTasks() {
    const response = await fetch(this.apiUrl + '?action=getTasks');
    const data = await response.json();
    this.tasks = [...data.message];
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
