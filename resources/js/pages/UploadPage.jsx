import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { ArrowLeft, CheckCircle } from 'lucide-react';
import api from '../api';

const SOURCES = {
    video: ['youtube', 'hls'],
    audio: ['local_audio', 'video_to_audio', 'youtube_to_audio'],
};

const SOURCE_LABELS = {
    youtube:     'YouTube',
    hls:         'Upload de vídeo (HLS)',
    local_audio: 'Upload de áudio',
    video_to_audio: 'Converter vídeo enviado para áudio',
    youtube_to_audio: 'YouTube URL/ID para áudio',
};

export default function UploadPage() {
    const [mediaType, setMediaType] = useState('video');
    const [source, setSource]       = useState('youtube');
    const [form, setForm]           = useState({ title: '', video_id: '' });
    const [file, setFile]           = useState(null);
    const [thumbnail, setThumbnail] = useState(null);
    const [loading, setLoading]     = useState(false);
    const [error, setError]         = useState(null);
    const [success, setSuccess]     = useState(false);
    const navigate = useNavigate();

    function changeMediaType(type) {
        setMediaType(type);
        setSource(SOURCES[type][0]);
        setFile(null);
    }

    async function handleSubmit(e) {
        e.preventDefault();
        setLoading(true);
        setError(null);

        try {
            const payload = new FormData();
            payload.append('media_type', mediaType);
            payload.append('source', source);
            if (form.title)    payload.append('title', form.title);
            if (form.video_id) payload.append('video_id', form.video_id);
            if (file)          payload.append('file', file);
            if (thumbnail)     payload.append('thumbnail', thumbnail);

            await api.post('/media', payload, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            setSuccess(true);
            setTimeout(() => navigate('/'), 1500);
        } catch (err) {
            const messages = err.response?.data;
            if (messages && typeof messages === 'object') {
                setError(Object.values(messages).flat().join(' '));
            } else {
                setError('Erro ao enviar mídia. Tente novamente.');
            }
        } finally {
            setLoading(false);
        }
    }

    const input    = "w-full bg-neutral-800 text-white placeholder-neutral-500 rounded-lg px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-white/20";
    const label    = "block text-sm font-medium text-neutral-300 mb-1.5";
    const fileInput = "w-full text-sm text-neutral-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-neutral-700 file:text-white hover:file:bg-neutral-600 cursor-pointer";

    if (success) {
        return (
            <div className="min-h-screen bg-neutral-950 flex items-center justify-center">
                <div className="text-center">
                    <CheckCircle size={56} className="text-green-400 mx-auto mb-4" />
                    <p className="text-white text-xl font-semibold">Mídia enviada com sucesso!</p>
                    <p className="text-neutral-500 text-sm mt-2">Redirecionando...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-neutral-950 text-white">
            <header className="bg-neutral-900 border-b border-neutral-800 px-6 py-4 flex items-center gap-4">
                <Link to="/" className="text-neutral-400 hover:text-white transition-colors">
                    <ArrowLeft size={20} />
                </Link>
                <h1 className="text-lg font-bold">Upload de Mídia</h1>
            </header>

            <div className="max-w-lg mx-auto px-6 py-8">
                {error && (
                    <div className="bg-red-900/30 border border-red-700 text-red-300 rounded-lg px-4 py-3 text-sm mb-6">
                        {error}
                    </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-6">

                    {/* Tipo de mídia */}
                    <div>
                        <label className={label}>Tipo de mídia</label>
                        <div className="grid grid-cols-2 gap-3">
                            {['video', 'audio'].map(type => (
                                <button
                                    key={type}
                                    type="button"
                                    onClick={() => changeMediaType(type)}
                                    className={`py-3 rounded-lg font-medium text-sm transition-colors capitalize ${
                                        mediaType === type
                                            ? 'bg-white text-black'
                                            : 'bg-neutral-800 text-neutral-300 hover:bg-neutral-700'
                                    }`}
                                >
                                    {type === 'video' ? 'Vídeo' : 'Áudio'}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Fonte */}
                    <div>
                        <label className={label}>Fonte</label>
                        <select
                            value={source}
                            onChange={e => setSource(e.target.value)}
                            className={input + ' bg-neutral-800'}
                        >
                            {SOURCES[mediaType].map(s => (
                                <option key={s} value={s}>{SOURCE_LABELS[s]}</option>
                            ))}
                        </select>
                    </div>

                    {/* Título */}
                    {source !== 'youtube' && source !== 'youtube_to_audio' && (
                        <div>
                            <label className={label}>Título</label>
                            <input
                                type="text"
                                placeholder="Título da mídia"
                                value={form.title}
                                onChange={e => setForm({ ...form, title: e.target.value })}
                                className={input}
                            />
                            {source === 'local_audio' && (
                                <p className="text-xs text-neutral-500 mt-1.5">
                                    Deixe em branco para usar o título das tags ID3 do arquivo.
                                </p>
                            )}
                        </div>
                    )}

                    {/* YouTube ID */}
                    {(source === 'youtube' || source === 'youtube_to_audio') && (
                        <div>
                            <label className={label}>
                                {source === 'youtube_to_audio' ? 'URL ou ID do vídeo no YouTube' : 'ID do vídeo no YouTube'}
                            </label>
                            <input
                                type="text"
                                placeholder={source === 'youtube_to_audio' ? 'ex: https://www.youtube.com/watch?v=dQw4w9WgXcQ' : 'ex: dQw4w9WgXcQ'}
                                value={form.video_id}
                                onChange={e => setForm({ ...form, video_id: e.target.value })}
                                className={input}
                            />
                            <p className="text-xs text-neutral-500 mt-1.5">
                                {source === 'youtube_to_audio'
                                    ? 'Cole o link completo do YouTube ou somente o ID do vídeo.'
                                    : <>
                                        Encontre na URL: youtube.com/watch?v=<strong className="text-neutral-400">dQw4w9WgXcQ</strong>
                                      </>
                                }
                            </p>
                        </div>
                    )}

                    {/* Arquivo */}
                    {['hls', 'local_audio', 'video_to_audio'].includes(source) && (
                        <div>
                            <label className={label}>
                                {source === 'local_audio' ? 'Arquivo de áudio' : 'Arquivo de vídeo'}
                            </label>
                            <input
                                type="file"
                                accept={source === 'local_audio' ? 'audio/*' : 'video/*'}
                                onChange={e => setFile(e.target.files[0])}
                                className={fileInput}
                            />
                            <p className="text-xs text-neutral-500 mt-1.5">
                                {source === 'local_audio'
                                    ? 'MP3, WAV, FLAC, OGG, AAC — máx 50MB. Tags e capa extraídas automaticamente.'
                                    : source === 'video_to_audio'
                                        ? 'MP4, MOV, OGG, MKV, WEBM — máx 200MB. O áudio será extraído para MP3.'
                                        : 'MP4, MOV — máx 200MB. Convertido para HLS em segundo plano via FFmpeg.'}
                            </p>
                        </div>
                    )}

                    {/* Thumbnail */}
                    {source !== 'youtube' && (
                        <div>
                            <label className={label}>
                                Thumbnail{' '}
                                <span className="text-neutral-500 font-normal">(opcional)</span>
                            </label>
                            <input
                                type="file"
                                accept="image/jpeg,image/png"
                                onChange={e => setThumbnail(e.target.files[0])}
                                className={fileInput}
                            />
                        </div>
                    )}

                    <button
                        type="submit"
                        disabled={loading}
                        className="w-full bg-white text-black font-semibold py-3 rounded-lg hover:bg-neutral-200 disabled:opacity-50 transition-colors text-sm"
                    >
                        {loading ? 'Enviando...' : 'Enviar Mídia'}
                    </button>
                </form>
            </div>
        </div>
    );
}
