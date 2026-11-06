<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';

import { api } from '../api.js';

const tasks = ref([]);
const loading = ref(true);
const error = ref('');
const title = ref('');
const busy = ref(false);

async function load () {
  loading.value = true;
  error.value = '';
  try {
    tasks.value = (await api.list()) ?? [];
  } catch (e) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
}

async function add () {
  const text = title.value.trim();
  if (text === '') return;

  busy.value = true;
  error.value = '';
  try {
    tasks.value.push(await api.create(text));
    title.value = '';
  } catch (e) {
    error.value = e.message;
  } finally {
    busy.value = false;
  }
}

async function toggle (task, event) {
  error.value = '';
  try {
    Object.assign(task, await api.update(task.id, { done: !task.done }));
  } catch (e) {
    // `:checked` only patches on change: put the box back when the PATCH fails.
    event.target.checked = task.done;
    error.value = e.message;
  }
}

async function remove (task) {
  error.value = '';
  try {
    await api.remove(task.id);
    tasks.value = tasks.value.filter((t) => t.id !== task.id);
  } catch (e) {
    error.value = e.message;
  }
}

onMounted(load);
</script>

<template>
  <section class="card">
    <h1>Tasks</h1>

    <form class="add" @submit.prevent="add">
      <input
        v-model="title"
        type="text"
        maxlength="120"
        placeholder="What needs to be done?"
        aria-label="New task title"
      />
      <button class="btn" type="submit" :disabled="busy || title.trim() === ''">Add</button>
    </form>

    <p v-if="error" class="error" role="alert">{{ error }}</p>

    <p v-if="loading" class="muted">Loading…</p>
    <p v-else-if="tasks.length === 0" class="muted">No tasks yet. Add the first one above.</p>
    <ul v-else class="tasks">
      <li v-for="task in tasks" :key="task.id" class="task" :class="{ done: task.done }">
        <input
          type="checkbox"
          :checked="task.done"
          :aria-label="`Mark “${task.title}” as ${task.done ? 'not done' : 'done'}`"
          @change="toggle(task, $event)"
        />
        <RouterLink :to="`/tasks/${task.id}`">{{ task.title }}</RouterLink>
        <button class="btn btn-danger" type="button" @click="remove(task)">Delete</button>
      </li>
    </ul>
  </section>
</template>
