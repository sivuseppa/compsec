import { store } from './store.js';
// import User from './user.js';

export default {
  props: {},
  data() {
    return {
      store,
    };
  },
  methods: {
    close() {
      store.notice.content = null;
    },
    getClass() {
      return `card notice-wrapper ${store.notice.type}`;
    },
  },
  template: `
            <div v-if="store.notice.content" :class="getClass()">
              <span>{{store.notice.content}}</span>
              <button @click="close">X</button>
            </div>`,
};
