import React, { useEffect, useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { ArrowLeft, Play, Music, Video, Trash2, Share2, LinkIcon, XCircle } from 'lucide-react';
import api from '../api';

export default function PlaylistDetailPage() {
    const { publicId } = useParams();
    const [playlist, setPlaylist] = useState(null);
    const [error, setError] = useState(null);
    const navigate = useNavigate();

    useEffect(() => { loadPlaylist(); }, [publicId]);

    async function loadPlaylist() {
        try {
            const { data } = await api.get(`/playlists/${publicId}`);
            setPlaylist(data.data ?? data);
        } catch {
            setError('Playlist não encontrada.');
        }
    }

    async function removeMedia(mediaPublicId) {
        await api.delete(`/playlists/${publicId}/media/${mediaPublicId}`);
        loadPlaylist();
    }

    async function toggleShare() {
        if (playlist.share_token) {
            await api.delete(`/playlists/${publicId}/share`);
        } else {
            await api.post(`/playlists/${publicId}/share`);
        }
        loadPlaylist();
    }

    async function copyShareLink() {
        const url = `${window.location.origin}/shared/playlist/${playlist.share_token}`;
        await navigator.clipboard.writeText(url);
        alert('Link copiado!');
    }

    if (error) {
        return (
            <div className="min-h-screen bg-neutral-950 flex flex-col items-center justify-center gap-4">
                <p className="text-white text-2xl">{error}</p>
                <Link to="/playlists" className="text-neutral-400 hover:text-white text-sm transition-colors">
                    Voltar
                </Link>
            </div>
        );
    }

    if (!playlist) {
        return (
            <div className="min-h-screen bg-neutral-950 flex items-center justify-center">
                <p className="text-neutral-600 text-xl">Carregando...</p>
            </div>
        );
    }

    const mediaItems = playlist.media ?? [];

    return (
        <div className="min-h-screen bg-neutral-950 text-white">
            <header className="bg-neutral-900 border-b border-neutral-800 px-6 py-4">
                <div className="flex items-center justify-between max-w-4xl mx-auto">
                    <div className="flex items-center gap-3">
                        <Link to="/playlists" className="text-neutral-400 hover:text-white transition-colors">
                            <ArrowLeft size={18} />
                        </Link>
                        <div>
                            <h1 className="text-lg font-bold">{playlist.name}</h1>
                            {playlist.description && (
                                <p className="text-sm text-neutral-400">{playlist.description}</p>
                            )}
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        {playlist.share_token && (
                            <button
                                onClick={copyShareLink}
                                className="flex items-center gap-1 text-sm text-emerald-400 hover:text-emerald-300 px-3 py-1.5 border border-emerald-400/30 rounded-lg transition-colors"
                            >
                                <LinkIcon size={14} /> Copiar Link
                            </button>
                        )}
                        <button
                            onClick={toggleShare}
                            className={`flex items-center gap-1 text-sm px-3 py-1.5 border rounded-lg transition-colors ${
                                playlist.is_public
                                    ? 'text-red-400 border-red-400/30 hover:text-red-300'
                                    : 'text-neutral-300 border-neutral-700 hover:text-white'
                            }`}
                        >
                            {playlist.is_public ? (
                                <><XCircle size={14} /> Revogar</>
                            ) : (
                                <><Share2 size={14} /> Compartilhar</>
                            )}
                        </button>
                    </div>
                </div>
            </header>

            <main className="max-w-4xl mx-auto px-6 py-6">
                <p className="text-sm text-neutral-500 mb-4">{mediaItems.length} itens na playlist</p>

                {mediaItems.length === 0 ? (
                    <div className="text-center py-16">
                        <p className="text-neutral-500 mb-4">Nenhuma mídia nesta playlist.</p>
                        <Link to="/" className="text-white underline text-sm">
                            Adicionar mídia
                        </Link>
                    </div>
                ) : (
                    <div className="space-y-2">
                        {mediaItems.map((item, index) => (
                            <div
                                key={item.public_id}
                                className="flex items-center gap-4 bg-neutral-900 border border-neutral-800 rounded-xl p-3 hover:bg-neutral-800/50 transition-colors group"
                            >
                                <span className="text-neutral-600 text-sm w-6 text-center">{index + 1}</span>

                                <div
                                    className="relative w-12 h-12 rounded-lg overflow-hidden bg-neutral-800 flex-shrink-0 cursor-pointer"
                                    onClick={() => navigate(`/player/${item.public_id}`)}
                                >
                                    {item.thumbnail_url ? (
                                        <img src={item.thumbnail_url} alt="" className="w-full h-full object-cover" />
                                    ) : item.media_type === 'audio' ? (
                                        <div className="w-full h-full flex items-center justify-center">
                                            <Music size={18} className="text-neutral-600" />
                                        </div>
                                    ) : (
                                        <div className="w-full h-full flex items-center justify-center">
                                            <Video size={18} className="text-neutral-600" />
                                        </div>
                                    )}
                                    <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                        <Play size={16} fill="white" className="text-white" />
                                    </div>
                                </div>

                                <div
                                    className="flex-1 min-w-0 cursor-pointer"
                                    onClick={() => navigate(`/player/${item.public_id}`)}
                                >
                                    <p className="text-sm font-medium truncate">{item.title}</p>
                                    {item.artist && (
                                        <p className="text-xs text-neutral-400 truncate">{item.artist}</p>
                                    )}
                                </div>

                                <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${
                                    item.media_type === 'audio'
                                        ? 'bg-purple-900/50 text-purple-300'
                                        : 'bg-blue-900/50 text-blue-300'
                                }`}>
                                    {item.media_type}
                                </span>

                                <button
                                    onClick={() => removeMedia(item.public_id)}
                                    className="p-2 text-neutral-500 hover:text-red-400 transition-colors opacity-0 group-hover:opacity-100"
                                    title="Remover da playlist"
                                >
                                    <Trash2 size={15} />
                                </button>
                            </div>
                        ))}
                    </div>
                )}
            </main>
        </div>
    );
}
