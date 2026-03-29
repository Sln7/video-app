import './bootstrap';
import React from 'react';
import { createRoot } from 'react-dom/client';
import UniversalPlayer from './components/UniversalPlayer';

const el = document.getElementById('root');

if (el) {
    // Bootstrap with media data injected from Blade into the root element
    const media = JSON.parse(el.dataset.media ?? 'null');
    createRoot(el).render(<UniversalPlayer media={media} />);
}
