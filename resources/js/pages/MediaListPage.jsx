import React, { useEffect, useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { Play, Upload, LogOut, Music, Video } from 'lucide-react';
import api from '../api';

export default function MediaListPage() {
    const [media, setMedia]     = useState([]);
    const [loading, setLoading] = useState(true);
    const [filter, setFilter]   = useState('');
    const navigate = useNavigate();

    useEffect(() => { loadMedia(); }, [filter]);

    async function loadMedia() {
        setLoading(true);
        try {
            const params = filter ? { media_type: filter } : {};
            const { data } = await api.get('/media', { params });
            setMedia(data.data ?? data);
        } finally {
            setLoading(false);
        }
    }

    function logout() {
        localStorage.removeItem('token');
        navigate('/login');
    }

    return (
        <div className="min-h-screen bg-neutral-950 text-white">

            {/* Header */}
            <header className="bg-neutral-900 border-b border-neutral-800 px-6 py-4 flex items-center justify-between">
                <h1 className="text-lg font-bold tracking-tight">Media Admin</h1>
                <div className="flex items-center gap-2">
                    <Link
                        to="/upload"
                        className="flex items-center gap-2 bg-white text-black px-4 py-2 rounded-lg font-semibold text-sm hover:bg-neutral-200 transition-colors"
                    >
                        <Upload size={15} /> Upload
                    </Link>
                    <button
                        onClick={logout}
                        className="flex items-center gap-1.5 text-neutral-400 hover:text-white px-3 py-2 rounded-lg transition-colors text-sm"
                    >
                        <LogOut size={15} /> Sair
                    </button>
                </div>
            </header>

            {/* Filtros */}
            <div className="px-6 py-4 flex gap-2">
                {[
                    { value: '',      label: 'Todos'   },
                    { value: 'video', label: 'Vídeos'  },
                    { value: 'audio', label: 'Áudios'  },
                ].map(({ value, label }) => (
                    <button
                        key={value}
                        onClick={() => setFilter(value)}
                        className={`px-4 py-1.5 rounded-full text-sm font-medium transition-colors ${
                            filter === value
                                ? 'bg-white text-black'
                                : 'bg-neutral-800 text-neutral-300 hover:bg-neutral-700'
                        }`}
                    >
                        {label}
                    </button>
                ))}
            </div>

            {/* Conteúdo */}
            <main className="px-6 pb-10">
                {loading ? (
                    <p className="text-neutral-600 text-center py-20">Carregando...</p>
                ) : media.length === 0 ? (
                    <div className="text-center py-24">
                        <p className="text-neutral-500 text-lg mb-4">Nenhuma mídia cadastrada ainda.</p>
                        <Link to="/upload" className="text-white underline text-sm">Fazer upload agora</Link>
                    </div>
                ) : (
                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                        {media.map(item => (
                            <div
                                key={item.public_id}
                                onClick={() => navigate(`/player/${item.public_id}`)}
                                className="cursor-pointer group bg-neutral-900 rounded-xl overflow-hidden hover:bg-neutral-800 transition-colors"
                            >
                                {/* Thumbnail */}
                                <div className="relative aspect-square bg-neutral-800 flex items-center justify-center overflow-hidden">
                                    {item.thumbnail ? (
                                        <img
                                            src={item.thumbnail}
                                            alt={item.title}
                                            className="w-full h-full object-cover"
                                        />
                                    ) : item.media_type === 'audio' ? (
                                        <Music size={36} className="text-neutral-600" />
                                    ) : (
                                        <Video size={36} className="text-neutral-600" />
                                    )}
                                    <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                        <Play size={32} fill="white" className="text-white" />
                                    </div>
                                </div>

                                {/* Info */}
                                <div className="p-3">
                                    <p className="text-sm font-medium truncate leading-snug">{item.title}</p>
                                    {item.artist && (
                                        <p className="text-xs text-neutral-400 truncate mt-0.5">{item.artist}</p>
                                    )}
                                    <span className={`inline-block mt-2 text-xs px-2 py-0.5 rounded-full font-medium ${
                                        item.media_type === 'audio'
                                            ? 'bg-purple-900/50 text-purple-300'
                                            : 'bg-blue-900/50 text-blue-300'
                                    }`}>
                                        {item.media_type}
                                    </span>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </main>
        </div>
    );
}
