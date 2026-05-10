import { store } from './store.js';
import Setting from './setting.js';

export default {
  components: {
    Setting,
  },
  props: {},
  data() {
    return {
      store,
    };
  },
  methods: {
    async saveSettings() {
      const response = await fetch(store.apiUrl, {
        method: 'POST',
        body: JSON.stringify({ settings: store.settings, action: 'saveSettings' }),
      });
      const data = await response.json();
      store.setNotice(data?.status, data?.message);
      store.fetchSettings();
    },
  },
  mounted() {
    store.fetchSettings();
  },
  template: `<div id="settingsPage">
              <table class="card">
                <thead>
                  <tr>
                    <td>Setting</td>
                    <td>Value</td>
                  </tr>
                </thead>
                <tbody>
                  <setting v-for="(setting, index) in store.settings" :index="index" :key="setting.id"></setting>
                </tbody>
              </table>
              <button @click.prevent="saveSettings">Save settings</button>
            </div>`,
};
