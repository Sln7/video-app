import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { ArrowLeft } from 'lucide-react';
import UniversalPlayer from '../components/UniversalPlayer';
import api from '../api';

export default function PlayerPage() {
    const { id } = useParams();
    const [media, setMedia]   = useState(null);
    const [error, setError]   = useState(null);

    useEffect(() => {
        api.get(`/media/${id}`)
            .then(({ data }) => setMedia(data.data ?? data))
            .catch(() => setError('Mídia não encontrada.'));
    }, [id]);

    if (error) {
        return (
            <div className="min-h-screen bg-black flex flex-col items-center justify-center gap-4">
                <p className="text-white text-2xl">{error}</p>
                <Link to="/" className="text-neutral-400 hover:text-white text-sm transition-colors">
                    ← Voltar
                </Link>
            </div>
        );
    }

    if (!media) {
        return (
            <div className="min-h-screen bg-black flex items-center justify-center">
                <p className="text-neutral-600 text-xl">Carregando...</p>
            </div>
        );
    }

    return (
        <div className="relative">
            <Link
                to="/"
                className="absolute top-5 left-5 z-10 flex items-center gap-1 bg-black/40 hover:bg-black/70 text-white/70 hover:text-white px-3 py-1.5 rounded-full text-sm transition-all backdrop-blur-sm"
            >
                <ArrowLeft size={14} /> Voltar
            </Link>
            <UniversalPlayer media={media} />
        </div>
    );
}
