import React, { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Plus, Music, Video, Trash2, Share2, ArrowLeft, LinkIcon, XCircle } from 'lucide-react';
import api from '../api';

export default function PlaylistsPage() {
    const [playlists, setPlaylists] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showCreate, setShowCreate] = useState(false);
    const [name, setName] = useState('');
    const [description, setDescription] = useState('');
    const [creating, setCreating] = useState(false);
    const navigate = useNavigate();

    useEffect(() => { loadPlaylists(); }, []);

    async function loadPlaylists() {
        setLoading(true);
        try {
            const { data } = await api.get('/playlists');
            setPlaylists(data.data ?? data);
        } finally {
            setLoading(false);
        }
    }

    async function createPlaylist(e) {
        e.preventDefault();
        if (!name.trim()) return;
        setCreating(true);
        try {
            await api.post('/playlists', { name, description });
            setName('');
            setDescription('');
            setShowCreate(false);
            loadPlaylists();
        } finally {
            setCreating(false);
        }
    }

    async function deletePlaylist(publicId) {
        if (!confirm('Tem certeza que deseja excluir esta playlist?')) return;
        await api.delete(`/playlists/${publicId}`);
        loadPlaylists();
    }

    async function toggleShare(playlist) {
        if (playlist.share_token) {
            await api.delete(`/playlists/${playlist.public_id}/share`);
        } else {
            await api.post(`/playlists/${playlist.public_id}/share`);
        }
        loadPlaylists();
    }

    async function copyShareLink(playlist) {
        const url = `${window.location.origin}/shared/playlist/${playlist.share_token}`;
        await navigator.clipboard.writeText(url);
        alert('Link copiado!');
    }

    return (
        <div className="min-h-screen bg-neutral-950 text-white">
            <header className="bg-neutral-900 border-b border-neutral-800 px-6 py-4 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <Link to="/" className="text-neutral-400 hover:text-white transition-colors">
                        <ArrowLeft size={18} />
                    </Link>
                    <h1 className="text-lg font-bold tracking-tight">Minhas Playlists</h1>
                </div>
                <button
                    onClick={() => setShowCreate(!showCreate)}
                    className="flex items-center gap-2 bg-white text-black px-4 py-2 rounded-lg font-semibold text-sm hover:bg-neutral-200 transition-colors"
                >
                    <Plus size={15} /> Nova Playlist
                </button>
            </header>

            <div className="max-w-4xl mx-auto px-6 py-6">
                {showCreate && (
                    <form onSubmit={createPlaylist} className="mb-8 bg-neutral-900 border border-neutral-800 rounded-xl p-6 space-y-4">
                        <input
                            type="text"
                            placeholder="Nome da playlist"
                            value={name}
                            onChange={e => setName(e.target.value)}
                            className="w-full bg-neutral-800 border border-neutral-700 rounded-lg px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:outline-none focus:border-neutral-500"
                            maxLength={120}
                        />
                        <textarea
                            placeholder="Descrição (opcional)"
                            value={description}
                            onChange={e => setDescription(e.target.value)}
                            className="w-full bg-neutral-800 border border-neutral-700 rounded-lg px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:outline-none focus:border-neutral-500 resize-none"
                            rows={2}
                            maxLength={1000}
                        />
                        <div className="flex gap-2">
                            <button
                                type="submit"
                                disabled={creating || !name.trim()}
                                className="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50"
                            >
                                {creating ? 'Criando...' : 'Criar'}
                            </button>
                            <button
                                type="button"
                                onClick={() => setShowCreate(false)}
                                className="bg-neutral-800 hover:bg-neutral-700 text-white px-4 py-2 rounded-lg text-sm transition-colors"
                            >
                                Cancelar
                            </button>
                        </div>
                    </form>
                )}

                {loading ? (
                    <p className="text-neutral-600 text-center py-20">Carregando...</p>
                ) : playlists.length === 0 ? (
                    <div className="text-center py-24">
                        <p className="text-neutral-500 text-lg mb-4">Nenhuma playlist criada ainda.</p>
                        <button
                            onClick={() => setShowCreate(true)}
                            className="text-white underline text-sm"
                        >
                            Criar primeira playlist
                        </button>
                    </div>
                ) : (
                    <div className="space-y-3">
                        {playlists.map(playlist => (
                            <div
                                key={playlist.public_id}
                                className="bg-neutral-900 border border-neutral-800 rounded-xl p-4 hover:bg-neutral-800/50 transition-colors"
                            >
                                <div className="flex items-center justify-between">
                                    <div
                                        className="flex-1 cursor-pointer"
                                        onClick={() => navigate(`/playlists/${playlist.public_id}`)}
                                    >
                                        <h3 className="font-semibold text-white">{playlist.name}</h3>
                                        {playlist.description && (
                                            <p className="text-sm text-neutral-400 mt-0.5 line-clamp-1">{playlist.description}</p>
                                        )}
                                        <div className="flex items-center gap-3 mt-2 text-xs text-neutral-500">
                                            <span className="flex items-center gap-1">
                                                <Music size={12} /> {playlist.media_count ?? 0} itens
                                            </span>
                                            {playlist.is_public && (
                                                <span className="text-emerald-400 flex items-center gap-1">
                                                    <Share2 size={12} /> Compartilhada
                                                </span>
                                            )}
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-1">
                                        {playlist.share_token && (
                                            <button
                                                onClick={() => copyShareLink(playlist)}
                                                className="p-2 text-neutral-400 hover:text-emerald-400 transition-colors"
                                                title="Copiar link"
                                            >
                                                <LinkIcon size={16} />
                                            </button>
                                        )}
                                        <button
                                            onClick={() => toggleShare(playlist)}
                                            className={`p-2 transition-colors ${
                                                playlist.is_public
                                                    ? 'text-emerald-400 hover:text-red-400'
                                                    : 'text-neutral-400 hover:text-emerald-400'
                                            }`}
                                            title={playlist.is_public ? 'Revogar compartilhamento' : 'Compartilhar'}
                                        >
                                            {playlist.is_public ? <XCircle size={16} /> : <Share2 size={16} />}
                                        </button>
                                        <button
                                            onClick={() => deletePlaylist(playlist.public_id)}
                                            className="p-2 text-neutral-400 hover:text-red-400 transition-colors"
                                            title="Excluir"
                                        >
                                            <Trash2 size={16} />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
