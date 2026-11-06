import { createRouter, createWebHistory } from 'vue-router';

import Tasks from './views/Tasks.vue';
import Task from './views/Task.vue';
import About from './views/About.vue';
import NotFound from './views/NotFound.vue';

// History mode under /vue/: Bootgly answers index.html for any extension-less path there.
export default createRouter({
  history: createWebHistory('/vue/'),
  routes: [
    { path: '/', component: Tasks },
    { path: '/tasks/:id', component: Task, props: true },
    { path: '/about', component: About },
    { path: '/:pathMatch(.*)*', component: NotFound },
  ],
});
