<script setup>
import { version } from 'vue';

// The view mounts on each visit to /about, so a one-time read is current.
const pathname = window.location.pathname;
</script>

<template>
  <section class="card">
    <h1>About</h1>
    <p>
      This page was reached by a <strong>client-side route</strong>: the router swapped the view
      without asking the server for a new document.
    </p>
    <p>
      Reloading it (F5) works too: Bootgly's <code>Web\App\SPA</code> handler answers
      <code>index.html</code> for any extension-less path under <code>/vue/</code>, and the
      client router takes over from there. Static files under <code>/vue/assets/</code> are
      served as immutable; a missing file with an extension is a real 404.
    </p>
    <dl class="facts">
      <dt>Path</dt>
      <dd><code>{{ pathname }}</code></dd>
      <dt>Framework</dt>
      <dd>Vue {{ version }}</dd>
    </dl>
  </section>
</template>
