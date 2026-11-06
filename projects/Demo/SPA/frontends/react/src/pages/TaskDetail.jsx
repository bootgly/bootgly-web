import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router';

import { api } from '../api.js';

export default function TaskDetail () {
  const { id } = useParams();
  const navigate = useNavigate();

  const [task, setTask] = useState(null);
  const [title, setTitle] = useState('');
  const [loading, setLoading] = useState(true);
  const [missing, setMissing] = useState(false);
  const [error, setError] = useState('');
  const [saved, setSaved] = useState(false);

  useEffect(() => {
    let active = true;
    setLoading(true);
    setMissing(false);
    setError('');
    setSaved(false);
    api.get(id)
      .then((data) => {
        if (!active) return;
        setTask(data);
        setTitle(data.title);
      })
      .catch((e) => {
        if (!active) return;
        if (e.status === 404) setMissing(true);
        else setError(e.message);
      })
      .finally(() => { if (active) setLoading(false); });
    return () => { active = false; };
  }, [id]);

  async function save (changes) {
    setError('');
    setSaved(false);
    try {
      const updated = await api.update(task.id, changes);
      setTask(updated);
      setTitle(updated.title);
      setSaved(true);
    } catch (e) {
      setError(e.message);
    }
  }

  async function remove () {
    setError('');
    try {
      await api.remove(task.id);
      navigate('/');
    } catch (e) {
      setError(e.message);
    }
  }

  if (loading) return <section className="card"><p className="muted">Loading…</p></section>;

  if (missing) {
    return (
      <section className="card">
        <h1>Task not found</h1>
        <p className="muted">There is no task #{id}.</p>
        <Link to="/">← Back to tasks</Link>
      </section>
    );
  }

  if (!task) {
    return (
      <section className="card">
        <p className="error" role="alert">{error}</p>
        <Link to="/">← Back to tasks</Link>
      </section>
    );
  }

  return (
    <section className="card">
      <p><Link to="/">← Back to tasks</Link></p>
      <h1>Task #{task.id}</h1>

      <form
        className="add"
        onSubmit={(e) => {
          e.preventDefault();
          save({ title: title.trim() });
        }}
      >
        <input
          type="text"
          maxLength={120}
          value={title}
          onChange={(e) => { setTitle(e.target.value); setSaved(false); }}
          aria-label="Task title"
        />
        <button className="btn" type="submit" disabled={title.trim() === '' || title.trim() === task.title}>Save</button>
      </form>

      <label className="check">
        <input type="checkbox" checked={task.done} onChange={() => save({ done: !task.done })} />
        Done
      </label>

      {saved && <p className="muted" role="status">Saved.</p>}
      {error && <p className="error" role="alert">{error}</p>}

      <p>
        <button className="btn btn-danger" type="button" onClick={remove}>Delete task</button>
      </p>
    </section>
  );
}
