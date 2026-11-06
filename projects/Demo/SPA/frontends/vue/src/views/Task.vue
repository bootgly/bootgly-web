<script setup>
import { ref, watch } from 'vue';
import { RouterLink, useRouter } from 'vue-router';

import { api } from '../api.js';

const props = defineProps({ id: { type: String, required: true } });
const router = useRouter();

const task = ref(null);
const title = ref('');
const loading = ref(true);
const missing = ref(false);
const error = ref('');
const saved = ref(false);

// The id of the last load: a slower answer for a task left behind is dropped.
let current = null;

async function load () {
  const id = props.id;
  current = id;
  loading.value = true;
  missing.value = false;
  error.value = '';
  saved.value = false;
  task.value = null;
  try {
    const found = await api.get(id);
    if (current !== id) return;
    task.value = found;
    title.value = found.title;
  } catch (e) {
    if (current !== id) return;
    if (e.status === 404) missing.value = true;
    else error.value = e.message;
  } finally {
    if (current === id) loading.value = false;
  }
}

async function save (changes, event) {
  error.value = '';
  saved.value = false;
  try {
    task.value = await api.update(task.value.id, changes);
    title.value = task.value.title;
    saved.value = true;
  } catch (e) {
    // `:checked` only patches on change: put the box back when the PATCH fails.
    if (event?.target.type === 'checkbox') event.target.checked = task.value.done;
    error.value = e.message;
  }
}

async function remove () {
  error.value = '';
  try {
    await api.remove(task.value.id);
    router.push('/');
  } catch (e) {
    error.value = e.message;
  }
}

watch(() => props.id, load, { immediate: true });
</script>

<template>
  <section v-if="loading" class="card"><p class="muted">Loading…</p></section>

  <section v-else-if="missing" class="card">
    <h1>Task not found</h1>
    <p class="muted">There is no task #{{ id }}.</p>
    <RouterLink to="/">← Back to tasks</RouterLink>
  </section>

  <section v-else-if="!task" class="card">
    <p class="error" role="alert">{{ error }}</p>
    <RouterLink to="/">← Back to tasks</RouterLink>
  </section>

  <section v-else class="card">
    <p><RouterLink to="/">← Back to tasks</RouterLink></p>
    <h1>Task #{{ task.id }}</h1>

    <form class="add" @submit.prevent="save({ title: title.trim() })">
      <input v-model="title" type="text" maxlength="120" aria-label="Task title" @input="saved = false" />
      <button class="btn" type="submit" :disabled="title.trim() === '' || title.trim() === task.title">Save</button>
    </form>

    <label class="check">
      <input type="checkbox" :checked="task.done" @change="save({ done: !task.done }, $event)" />
      Done
    </label>

    <p v-if="saved" class="muted" role="status">Saved.</p>
    <p v-if="error" class="error" role="alert">{{ error }}</p>

    <p><button class="btn btn-danger" type="button" @click="remove">Delete task</button></p>
  </section>
</template>
