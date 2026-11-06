<script setup>
import { computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';

const route = useRoute();
// The real browser path (with the /vue base), re-read whenever the route changes.
const pathname = computed(() => route.fullPath && window.location.pathname);
</script>

<template>
  <section class="card">
    <h1>Page not found</h1>
    <p class="muted">
      The server answered <code>index.html</code> for <code>{{ pathname }}</code>;
      the client router has no route for it.
    </p>
    <RouterLink to="/">← Back to tasks</RouterLink>
  </section>
</template>
