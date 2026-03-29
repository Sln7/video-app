import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../api';

export default function LoginPage() {
    const [form, setForm]       = useState({ email: '', password: '' });
    const [error, setError]     = useState(null);
    const [loading, setLoading] = useState(false);
    const navigate = useNavigate();

    async function handleSubmit(e) {
        e.preventDefault();
        setLoading(true);
        setError(null);
        try {
            const { data } = await api.post('/login', form);
            localStorage.setItem('token', data.token);
            navigate('/');
        } catch {
            setError('E-mail ou senha incorretos.');
        } finally {
            setLoading(false);
        }
    }

    return (
        <div className="min-h-screen bg-neutral-950 flex items-center justify-center px-4">
            <div className="bg-neutral-900 rounded-2xl p-8 w-full max-w-sm shadow-xl">
                <h1 className="text-white text-2xl font-bold mb-2">Media Admin</h1>
                <p className="text-neutral-500 text-sm mb-8">Entre com suas credenciais para continuar.</p>

                {error && (
                    <div className="bg-red-900/30 border border-red-700 text-red-300 rounded-lg px-4 py-3 text-sm mb-6">
                        {error}
                    </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-4">
                    <input
                        type="email"
                        placeholder="E-mail"
                        value={form.email}
                        onChange={e => setForm({ ...form, email: e.target.value })}
                        required
                        className="w-full bg-neutral-800 text-white placeholder-neutral-500 rounded-lg px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-white/20"
                    />
                    <input
                        type="password"
                        placeholder="Senha"
                        value={form.password}
                        onChange={e => setForm({ ...form, password: e.target.value })}
                        required
                        className="w-full bg-neutral-800 text-white placeholder-neutral-500 rounded-lg px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-white/20"
                    />
                    <button
                        type="submit"
                        disabled={loading}
                        className="w-full bg-white text-black font-semibold py-3 rounded-lg hover:bg-neutral-200 disabled:opacity-50 transition-colors text-sm"
                    >
                        {loading ? 'Entrando...' : 'Entrar'}
                    </button>
                </form>
            </div>
        </div>
    );
}
