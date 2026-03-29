import './bootstrap';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import LoginPage from './pages/LoginPage';
import MediaListPage from './pages/MediaListPage';
import UploadPage from './pages/UploadPage';
import PlayerPage from './pages/PlayerPage';

function ProtectedRoute({ children }) {
    return localStorage.getItem('token')
        ? children
        : <Navigate to="/login" replace />;
}

function App() {
    return (
        <BrowserRouter>
            <Routes>
                <Route path="/login"       element={<LoginPage />} />
                <Route path="/player/:id"  element={<PlayerPage />} />
                <Route path="/"            element={<ProtectedRoute><MediaListPage /></ProtectedRoute>} />
                <Route path="/upload"      element={<ProtectedRoute><UploadPage /></ProtectedRoute>} />
                <Route path="*"            element={<Navigate to="/" replace />} />
            </Routes>
        </BrowserRouter>
    );
}

createRoot(document.getElementById('root')).render(<App />);
