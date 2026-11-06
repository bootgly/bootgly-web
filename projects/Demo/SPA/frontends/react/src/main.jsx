import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router';

import App from './App.jsx';
import './style.css';

createRoot(document.getElementById('app')).render(
  <StrictMode>
    <BrowserRouter basename="/react">
      <App />
    </BrowserRouter>
  </StrictMode>,
);
