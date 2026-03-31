import React, { useEffect, useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { Play, Music, Video, ListMusic } from 'lucide-react';
import api from '../api';

export default function SharedPlaylistPage() {
    const { shareToken } = useParams();
    const [playlist, setPlaylist] = useState(null);
    const [error, setError] = useState(null);
    const navigate = useNavigate();

    useEffect(() => {
        api.get(`/shared/playlist/${shareToken}`)
            .then(({ data }) => setPlaylist(data.data ?? data))
            .catch(() => setError('Playlist não encontrada ou não está mais compartilhada.'));
    }, [shareToken]);

    if (error) {
        return (
            <div className="min-h-screen bg-neutral-950 flex flex-col items-center justify-center gap-4">
                <p className="text-white text-xl">{error}</p>
                <Link to="/" className="text-neutral-400 hover:text-white text-sm transition-colors">
                    Ir para o início
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
            <header className="bg-neutral-900 border-b border-neutral-800 px-6 py-6">
                <div className="max-w-4xl mx-auto text-center">
                    <div className="inline-flex items-center gap-2 text-xs text-neutral-500 uppercase tracking-widest mb-3">
                        <ListMusic size={14} /> Playlist Compartilhada
                    </div>
                    <h1 className="text-2xl font-bold">{playlist.name}</h1>
                    {playlist.description && (
                        <p className="text-sm text-neutral-400 mt-2 max-w-xl mx-auto">{playlist.description}</p>
                    )}
                    {playlist.user && (
                        <p className="text-xs text-neutral-500 mt-2">por {playlist.user.name}</p>
                    )}
                    <p className="text-xs text-neutral-600 mt-1">{mediaItems.length} itens</p>
                </div>
            </header>

            <main className="max-w-4xl mx-auto px-6 py-6">
                {mediaItems.length === 0 ? (
                    <p className="text-center text-neutral-500 py-16">Esta playlist está vazia.</p>
                ) : (
                    <div className="space-y-2">
                        {mediaItems.map((item, index) => (
                            <div
                                key={item.public_id}
                                onClick={() => navigate(`/player/${item.public_id}`)}
                                className="flex items-center gap-4 bg-neutral-900 border border-neutral-800 rounded-xl p-3 hover:bg-neutral-800/50 transition-colors cursor-pointer group"
                            >
                                <span className="text-neutral-600 text-sm w-6 text-center">{index + 1}</span>

                                <div className="relative w-12 h-12 rounded-lg overflow-hidden bg-neutral-800 flex-shrink-0">
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

                                <div className="flex-1 min-w-0">
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
                            </div>
                        ))}
                    </div>
                )}
            </main>
        </div>
    );
}
