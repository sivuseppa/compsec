import { store } from './store.js';

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
              <button class="rounded-icon" @click="close">
                <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#ffffff"><path d="m291-240-51-51 189-189-189-189 51-51 189 189 189-189 51 51-189 189 189 189-51 51-189-189-189 189Z"/></svg>
              </button>
            </div>`,
};
