import { version } from 'react';
import { useLocation } from 'react-router';

export default function About () {
  // Re-render on client navigation; the value shown is the real browser path.
  useLocation();

  return (
    <section className="card">
      <h1>About</h1>
      <p>
        This page was reached by a <strong>client-side route</strong>: the router swapped the view
        without asking the server for a new document.
      </p>
      <p>
        Reloading it (F5) works too: Bootgly's <code>Web\App\SPA</code> handler answers{' '}
        <code>index.html</code> for any extension-less path under <code>/react/</code>, and the
        client router takes over from there. Static files under <code>/react/assets/</code> are
        served as immutable; a missing file with an extension is a real 404.
      </p>
      <dl className="facts">
        <dt>Path</dt>
        <dd><code>{window.location.pathname}</code></dd>
        <dt>Framework</dt>
        <dd>React {version}</dd>
      </dl>
    </section>
  );
}
