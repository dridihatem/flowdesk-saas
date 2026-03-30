import './bootstrap';

import Alpine from 'alpinejs';
import { createApp } from 'vue';
import FlowdeskPulse from './vue/FlowdeskPulse.vue';

window.Alpine = Alpine;

Alpine.start();

const vueRoot = document.getElementById('flowdesk-vue-root');
if (vueRoot) {
    const appName = vueRoot.dataset.appName ?? 'Flowdesk';
    createApp(FlowdeskPulse, { appName }).mount(vueRoot);
}
