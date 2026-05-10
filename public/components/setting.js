import { store } from './store.js';

export default {
  props: {
    index: Number,
  },
  data() {
    return { store };
  },
  methods: {},
  template: `<tr>
              <td>{{ store.settings[index].label }}</td>
              <td><input  name="userNewPassword" type="text" v-model="store.settings[index].value" /></td>
            </tr>`,
};
