import { Link, useLocation } from 'react-router';

export default function NotFound () {
  // Re-render on client navigation; the value shown is the real browser path.
  useLocation();

  return (
    <section className="card">
      <h1>Page not found</h1>
      <p className="muted">
        The server answered <code>index.html</code> for <code>{window.location.pathname}</code>;
        the client router has no route for it.
      </p>
      <Link to="/">← Back to tasks</Link>
    </section>
  );
}
