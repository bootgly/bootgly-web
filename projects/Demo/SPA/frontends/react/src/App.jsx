import { NavLink, Route, Routes } from 'react-router';

import About from './pages/About.jsx';
import NotFound from './pages/NotFound.jsx';
import TaskDetail from './pages/TaskDetail.jsx';
import Tasks from './pages/Tasks.jsx';

export default function App () {
  return (
    <div className="app">
      <header className="nav">
        <strong className="brand">Bootgly SPA · React</strong>
        <nav>
          <NavLink to="/" end>Tasks</NavLink>
          <NavLink to="/about">About</NavLink>
          {/* Plain links: full page loads, outside this app's router. */}
          <a href="/">Landing</a>
          <a href="/vue/">Vue app</a>
        </nav>
      </header>

      <main>
        <Routes>
          <Route path="/" element={<Tasks />} />
          <Route path="/tasks/:id" element={<TaskDetail />} />
          <Route path="/about" element={<About />} />
          <Route path="*" element={<NotFound />} />
        </Routes>
      </main>
    </div>
  );
}
