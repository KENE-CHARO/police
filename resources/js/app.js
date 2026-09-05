import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './App.jsx';
import '../css/app.css';

const root = document.getElementById('app');

if (root) {
    createRoot(root).render(React.createElement(App));
}
