import { useEffect, useState } from 'react';
import { Link } from 'react-router';

import { api } from '../api.js';

export default function Tasks () {
  const [tasks, setTasks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [title, setTitle] = useState('');
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    let active = true;
    api.list()
      .then((data) => { if (active) setTasks(data ?? []); })
      .catch((e) => { if (active) setError(e.message); })
      .finally(() => { if (active) setLoading(false); });
    return () => { active = false; };
  }, []);

  async function add (event) {
    event.preventDefault();
    const text = title.trim();
    if (text === '') return;

    setBusy(true);
    setError('');
    try {
      const task = await api.create(text);
      setTasks((list) => [...list, task]);
      setTitle('');
    } catch (e) {
      setError(e.message);
    } finally {
      setBusy(false);
    }
  }

  async function toggle (task) {
    setError('');
    try {
      const updated = await api.update(task.id, { done: !task.done });
      setTasks((list) => list.map((t) => (t.id === updated.id ? updated : t)));
    } catch (e) {
      setError(e.message);
    }
  }

  async function remove (task) {
    setError('');
    try {
      await api.remove(task.id);
      setTasks((list) => list.filter((t) => t.id !== task.id));
    } catch (e) {
      setError(e.message);
    }
  }

  return (
    <section className="card">
      <h1>Tasks</h1>

      <form className="add" onSubmit={add}>
        <input
          type="text"
          maxLength={120}
          value={title}
          onChange={(e) => setTitle(e.target.value)}
          placeholder="What needs to be done?"
          aria-label="New task title"
        />
        <button className="btn" type="submit" disabled={busy || title.trim() === ''}>Add</button>
      </form>

      {error && <p className="error" role="alert">{error}</p>}

      {loading ? (
        <p className="muted">Loading…</p>
      ) : tasks.length === 0 ? (
        <p className="muted">No tasks yet. Add the first one above.</p>
      ) : (
        <ul className="tasks">
          {tasks.map((task) => (
            <li key={task.id} className={task.done ? 'task done' : 'task'}>
              <input
                type="checkbox"
                checked={task.done}
                onChange={() => toggle(task)}
                aria-label={`Mark “${task.title}” as ${task.done ? 'not done' : 'done'}`}
              />
              <Link to={`/tasks/${task.id}`}>{task.title}</Link>
              <button className="btn btn-danger" type="button" onClick={() => remove(task)}>Delete</button>
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}
