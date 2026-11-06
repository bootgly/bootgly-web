<script setup>
import { RouterLink, RouterView } from 'vue-router';
</script>

<template>
  <div class="app">
    <header class="nav">
      <strong class="brand">Bootgly SPA · Vue</strong>
      <nav>
        <RouterLink to="/">Tasks</RouterLink>
        <RouterLink to="/about">About</RouterLink>
        <!-- Full page loads, outside this app -->
        <a href="/">Landing</a>
        <a href="/react/">React app</a>
      </nav>
    </header>
    <main>
      <RouterView />
    </main>
  </div>
</template>
