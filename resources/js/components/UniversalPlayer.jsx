import React, { useState, useRef, useEffect, useCallback } from 'react';
import ReactPlayer from 'react-player';
import {
    Play, Pause, Volume2, VolumeX, Music,
    Maximize2, Minimize2, SkipBack, SkipForward,
} from 'lucide-react';

function resolveUrl(media) {
    if (!media) return null;
    if (media.hls_url)   return media.hls_url;
    if (media.media_path) return media.media_path;
    if (media.embed_url)  return media.embed_url;
    return null;
}

function normalizeYouTubeEmbedUrl(rawUrl) {
    if (!rawUrl) return rawUrl;

    try {
        const url = new URL(rawUrl);
        if (!url.hostname.includes('youtube.com') && !url.hostname.includes('youtu.be')) return rawUrl;

        let videoId = '';

        if (url.hostname.includes('youtu.be')) {
            videoId = url.pathname.replace('/', '');
        } else {
            const pathParts = url.pathname.split('/').filter(Boolean);
            const embedIndex = pathParts.indexOf('embed');
            if (embedIndex >= 0 && pathParts[embedIndex + 1]) {
                videoId = pathParts[embedIndex + 1];
            } else {
                videoId = url.searchParams.get('v') || '';
            }
        }

        if (!videoId) return rawUrl;

        const safeUrl = new URL(`https://www.youtube-nocookie.com/embed/${videoId}`);

        // Reduce distractions and recommendations for child-focused viewing.
        safeUrl.searchParams.set('controls', '1');
        safeUrl.searchParams.set('rel', '0');
        safeUrl.searchParams.set('modestbranding', '1');
        safeUrl.searchParams.set('playsinline', '1');
        safeUrl.searchParams.set('iv_load_policy', '3');
        safeUrl.searchParams.set('fs', '0');
        safeUrl.searchParams.set('disablekb', '1');
        safeUrl.searchParams.delete('enablejsapi');
        safeUrl.searchParams.delete('origin');

        return safeUrl.toString();
    } catch {
        return rawUrl;
    }
}

function formatTime(seconds) {
    if (!seconds || isNaN(seconds)) return '0:00';
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    return `${m}:${s.toString().padStart(2, '0')}`;
}

export default function UniversalPlayer({ media }) {
    const [playing, setPlaying]       = useState(false);
    const [muted, setMuted]           = useState(false);
    const [volume, setVolume]         = useState(0.8);
    const [played, setPlayed]         = useState(0);      // 0–1
    const [duration, setDuration]     = useState(0);
    const [ready, setReady]           = useState(false);
    const [seeking, setSeeking]       = useState(false);
    const [showControls, setShowControls] = useState(true);
    const [fullscreen, setFullscreen] = useState(false);

    const playerRef   = useRef(null);
    const audioRef = useRef(null);
    const audioProgressRef = useRef(null);
    const containerRef = useRef(null);
    const hideTimer   = useRef(null);
    const pendingSeekRef = useRef(0);
    const [audioError, setAudioError] = useState('');

    const isAudio = media?.media_type === 'audio';
    const isYT    = media?.source === 'youtube';
    const resolvedUrl = resolveUrl(media);
    const url = isYT ? normalizeYouTubeEmbedUrl(resolvedUrl) : resolvedUrl;

    function seekToSeconds(seconds) {
        if (isAudio) {
            if (!audioRef.current || !duration) return;
            const clamped = Math.max(0, Math.min(duration, seconds));
            audioRef.current.currentTime = clamped;
            return;
        }
        playerRef.current?.seekTo(seconds, 'seconds');
    }

    function seekToFraction(fraction) {
        if (isAudio) {
            if (!audioRef.current || !duration) return;
            const clamped = Math.max(0, Math.min(1, fraction));
            audioRef.current.currentTime = clamped * duration;
            setPlayed(clamped);
            return;
        }
        playerRef.current?.seekTo(fraction, 'fraction');
    }

    function seekAudioFromPointer(clientX) {
        if (!isAudio || !audioProgressRef.current) return;

        const rect = audioProgressRef.current.getBoundingClientRect();
        if (!rect.width) return;

        const fraction = (clientX - rect.left) / rect.width;
        const clamped = Math.max(0, Math.min(1, fraction));

        pendingSeekRef.current = clamped;
        setPlayed(clamped);
        seekToFraction(clamped);
    }

    // ── Keyboard shortcuts ──────────────────────────────────────────────
    useEffect(() => {
        function onKey(e) {
            if (e.target.tagName === 'INPUT') return;
            if (e.code === 'Space')       { e.preventDefault(); setPlaying(v => !v); }
            if (e.code === 'KeyM')        setMuted(v => !v);
            if (e.code === 'ArrowRight')  seekToSeconds(played * duration + 10);
            if (e.code === 'ArrowLeft')   seekToSeconds(Math.max(0, played * duration - 10));
        }
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [played, duration, isAudio]);

    useEffect(() => {
        if (!isAudio || !audioRef.current) return;

        const audio = audioRef.current;
        audio.volume = volume;
        audio.muted = muted;

        if (playing) {
            audio.play().catch(() => {
                setPlaying(false);
                setAudioError('Nao foi possivel reproduzir este audio.');
            });
        } else {
            audio.pause();
        }
    }, [playing, isAudio, volume, muted]);

    // ── Auto-hide controls for video ───────────────────────────────────
    const resetHideTimer = useCallback(() => {
        if (isAudio) return;
        setShowControls(true);
        clearTimeout(hideTimer.current);
        hideTimer.current = setTimeout(() => {
            if (playing) setShowControls(false);
        }, 3000);
    }, [isAudio, playing]);

    useEffect(() => {
        if (!isAudio && playing) resetHideTimer();
        else setShowControls(true);
        return () => clearTimeout(hideTimer.current);
    }, [playing, isAudio, resetHideTimer]);

    // ── Fullscreen API ─────────────────────────────────────────────────
    function toggleFullscreen() {
        if (!document.fullscreenElement) {
            containerRef.current?.requestFullscreen();
            setFullscreen(true);
        } else {
            document.exitFullscreen();
            setFullscreen(false);
        }
    }

    useEffect(() => {
        function onFsChange() { setFullscreen(!!document.fullscreenElement); }
        document.addEventListener('fullscreenchange', onFsChange);
        return () => document.removeEventListener('fullscreenchange', onFsChange);
    }, []);

    if (!media || !url) {
        return (
            <div className="flex items-center justify-center w-full h-screen bg-black">
                <p className="text-white text-2xl">Nenhuma mídia selecionada.</p>
            </div>
        );
    }

    // ── Progress bar interaction ───────────────────────────────────────
    function onSeekChange(e) {
        const nextValue = parseFloat(e.target.value);
        pendingSeekRef.current = nextValue;
        setPlayed(nextValue);

        if (isAudio) {
            seekToFraction(nextValue);
        }
    }
    function onSeekMouseDown() { setSeeking(true); }
    function onSeekMouseUp() {
        setSeeking(false);
        seekToFraction(pendingSeekRef.current);
    }
    function onProgress(state) {
        if (!seeking) setPlayed(state.played);
    }

    function onAudioTimeUpdate() {
        if (!audioRef.current || seeking || !duration) return;
        setPlayed(audioRef.current.currentTime / duration);
    }

    function onAudioLoadedMetadata() {
        if (!audioRef.current) return;
        setDuration(audioRef.current.duration || 0);
        setReady(true);
        setAudioError('');
        pendingSeekRef.current = 0;
    }

    // ── Shared controls bar ────────────────────────────────────────────
    const Controls = ({ overlay = false }) => (
        <div
            className={`
                ${overlay
                    ? 'absolute bottom-0 left-0 right-0 px-4 pb-4 pt-16 bg-gradient-to-t from-black/80 to-transparent'
                    : 'w-full px-4 py-4'}
                transition-opacity duration-300
                ${overlay && !showControls ? 'opacity-0' : 'opacity-100'}
            `}
        >
            {/* Progress bar */}
            {!isYT && (
                <div className="flex items-center gap-3 mb-3">
                    <span className="text-white/70 text-xs tabular-nums w-10 text-right">
                        {formatTime(played * duration)}
                    </span>
                    <div className="relative flex-1 h-1 group">
                        <div className="absolute inset-0 rounded-full bg-white/20" />
                        <div
                            className="absolute left-0 top-0 h-full rounded-full bg-white"
                            style={{ width: `${played * 100}%` }}
                        />
                        <input
                            type="range" min={0} max={1} step="any"
                            value={played}
                            onMouseDown={onSeekMouseDown}
                            onMouseUp={onSeekMouseUp}
                            onChange={onSeekChange}
                            className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                        />
                    </div>
                    <span className="text-white/70 text-xs tabular-nums w-10">
                        {formatTime(duration)}
                    </span>
                </div>
            )}

            {/* Buttons row */}
            <div className="flex items-center justify-between">
                {/* Left: volume */}
                <div className="flex items-center gap-3 w-36">
                    <button
                        onClick={() => setMuted(v => !v)}
                        className="text-white/80 hover:text-white transition-colors"
                        aria-label={muted ? 'Unmute' : 'Mute'}
                    >
                        {muted || volume === 0
                            ? <VolumeX size={20} />
                            : <Volume2 size={20} />
                        }
                    </button>
                    <input
                        type="range" min={0} max={1} step="any"
                        value={muted ? 0 : volume}
                        onChange={e => { setVolume(parseFloat(e.target.value)); setMuted(false); }}
                        className="w-20 accent-white cursor-pointer"
                        aria-label="Volume"
                    />
                </div>

                {/* Center: seek back / play-pause / seek forward */}
                <div className="flex items-center gap-4">
                    {!isYT && (
                        <button
                            onClick={() => seekToSeconds(Math.max(0, played * duration - 10))}
                            className="text-white/70 hover:text-white transition-colors"
                            aria-label="-10s"
                        >
                            <SkipBack size={20} />
                        </button>
                    )}

                    <button
                        onClick={() => setPlaying(v => !v)}
                        disabled={!isAudio && !ready}
                        className="flex items-center justify-center w-12 h-12 rounded-full bg-white hover:bg-white/90 active:scale-95 transition-all disabled:opacity-40"
                        aria-label={playing ? 'Pause' : 'Play'}
                    >
                        {playing
                            ? <Pause  className="text-black" size={22} fill="black" />
                            : <Play   className="text-black ml-0.5" size={22} fill="black" />
                        }
                    </button>

                    {!isYT && (
                        <button
                            onClick={() => seekToFraction(Math.min(1, played + 10 / duration))}
                            className="text-white/70 hover:text-white transition-colors"
                            aria-label="+10s"
                        >
                            <SkipForward size={20} />
                        </button>
                    )}
                </div>

                {/* Right: fullscreen (video only) */}
                <div className="w-36 flex justify-end">
                    {!isAudio && (
                        <button
                            onClick={toggleFullscreen}
                            className="text-white/70 hover:text-white transition-colors"
                            aria-label="Fullscreen"
                        >
                            {fullscreen ? <Minimize2 size={18} /> : <Maximize2 size={18} />}
                        </button>
                    )}
                </div>
            </div>
        </div>
    );

    const AudioControls = () => (
        <div className="mt-8 w-full space-y-6">
            <div className="rounded-2xl border border-white/10 bg-white/5 px-4 py-4 shadow-lg shadow-black/20">
                <div className="flex items-center justify-between text-[11px] uppercase tracking-[0.24em] text-white/45">
                    <span>Agora tocando</span>
                    <span>{Math.round(played * 100)}%</span>
                </div>

                <div className="mt-4 flex items-center gap-3">
                    <span className="w-11 text-right text-sm tabular-nums text-white/75">
                        {formatTime(played * duration)}
                    </span>
                    <div
                        ref={audioProgressRef}
                        className="relative flex-1 cursor-pointer py-3"
                        onPointerDown={event => seekAudioFromPointer(event.clientX)}
                    >
                        <div className="h-2 rounded-full bg-white/10" />
                        <div
                            className="absolute left-0 top-0 h-2 rounded-full bg-gradient-to-r from-cyan-300 via-emerald-300 to-lime-200"
                            style={{ top: '0.75rem', width: `${played * 100}%` }}
                        />
                        <div
                            className="absolute top-1/2 h-4 w-4 -translate-y-1/2 rounded-full border border-white/30 bg-white shadow-[0_0_0_6px_rgba(255,255,255,0.08)]"
                            style={{ left: `calc(${played * 100}% - 0.5rem)` }}
                        />
                    </div>
                    <span className="w-11 text-sm tabular-nums text-white/55">
                        {formatTime(duration)}
                    </span>
                </div>
            </div>

            <div className="flex items-center justify-center gap-4 sm:gap-6">
                <button
                    onClick={() => seekToSeconds(Math.max(0, played * duration - 10))}
                    className="flex h-12 w-12 items-center justify-center rounded-full border border-white/10 bg-white/8 text-white/80 transition hover:bg-white/14 hover:text-white"
                    aria-label="-10s"
                >
                    <SkipBack size={20} />
                </button>

                <button
                    onClick={() => setPlaying(v => !v)}
                    className="flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-cyan-300 via-emerald-300 to-lime-200 text-black shadow-[0_16px_40px_rgba(52,211,153,0.3)] transition hover:scale-[1.02] active:scale-95"
                    aria-label={playing ? 'Pause' : 'Play'}
                >
                    {playing
                        ? <Pause className="text-black" size={30} fill="black" />
                        : <Play className="ml-1 text-black" size={30} fill="black" />
                    }
                </button>

                <button
                    onClick={() => seekToFraction(Math.min(1, played + 10 / Math.max(duration, 1)))}
                    className="flex h-12 w-12 items-center justify-center rounded-full border border-white/10 bg-white/8 text-white/80 transition hover:bg-white/14 hover:text-white"
                    aria-label="+10s"
                >
                    <SkipForward size={20} />
                </button>
            </div>

            <div className="grid gap-3 sm:grid-cols-[auto_1fr] sm:items-center rounded-2xl border border-white/10 bg-black/20 px-4 py-4">
                <button
                    onClick={() => setMuted(v => !v)}
                    className="flex h-11 w-11 items-center justify-center rounded-full bg-white/8 text-white/80 transition hover:bg-white/14 hover:text-white"
                    aria-label={muted ? 'Unmute' : 'Mute'}
                >
                    {muted || volume === 0
                        ? <VolumeX size={20} />
                        : <Volume2 size={20} />
                    }
                </button>

                <div>
                    <div className="mb-2 flex items-center justify-between text-xs uppercase tracking-[0.18em] text-white/45">
                        <span>Volume</span>
                        <span>{Math.round((muted ? 0 : volume) * 100)}%</span>
                    </div>
                    <input
                        type="range"
                        min={0}
                        max={1}
                        step="any"
                        value={muted ? 0 : volume}
                        onChange={e => { setVolume(parseFloat(e.target.value)); setMuted(false); }}
                        className="h-2 w-full cursor-pointer accent-emerald-300"
                        aria-label="Volume"
                    />
                </div>
            </div>
        </div>
    );

    // ══════════════════════════════════════════════════════════════════
    // AUDIO LAYOUT
    // ══════════════════════════════════════════════════════════════════
    if (isAudio) {
        return (
            <div
                className="relative flex flex-col items-center justify-center w-full min-h-screen overflow-hidden select-none"
                style={media.thumbnail_url ? {
                    backgroundImage: `url(${media.thumbnail_url})`,
                    backgroundSize: 'cover',
                    backgroundPosition: 'center',
                } : {
                    background: 'radial-gradient(circle at 20% 20%, #2f385e 0%, #0b1022 50%, #06080f 100%)',
                }}
            >
                <div className="absolute inset-0 bg-black/55 backdrop-blur-2xl" />
                <div className="absolute -top-32 -left-24 w-96 h-96 rounded-full bg-cyan-400/20 blur-3xl" />
                <div className="absolute -bottom-40 -right-20 w-[26rem] h-[26rem] rounded-full bg-emerald-400/20 blur-3xl" />
                <div className="absolute inset-x-0 top-0 h-40 bg-gradient-to-b from-white/10 to-transparent" />

                <div className="relative z-10 w-full max-w-5xl px-5 py-8 sm:py-12">
                    <div className="grid gap-8 rounded-[2rem] border border-white/10 bg-black/35 p-6 shadow-2xl backdrop-blur-xl sm:p-8 lg:grid-cols-[320px_minmax(0,1fr)] lg:items-center lg:gap-10">
                        <div className="relative mx-auto w-full max-w-[320px]">
                            <div className="absolute inset-6 rounded-full bg-emerald-300/20 blur-3xl" />
                            <div
                                className="relative aspect-square overflow-hidden rounded-[2rem] border border-white/10 bg-neutral-900 shadow-[0_30px_80px_rgba(0,0,0,0.45)]"
                                style={{
                                    transform: playing ? 'rotate(-2deg)' : 'rotate(0deg)',
                                    transition: 'transform 240ms ease',
                                }}
                            >
                                {media.thumbnail_url ? (
                                    <img
                                        src={media.thumbnail_url}
                                        alt={media.title}
                                        className="h-full w-full object-cover"
                                    />
                                ) : (
                                    <div className="flex h-full w-full items-center justify-center bg-neutral-800">
                                        <Music size={96} className="text-neutral-600" strokeWidth={1} />
                                    </div>
                                )}

                                <div className="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-black/70 to-transparent" />
                                <div className="absolute bottom-4 left-4 rounded-full border border-white/10 bg-black/40 px-3 py-1 text-[11px] uppercase tracking-[0.24em] text-white/70 backdrop-blur-sm">
                                    Local Audio
                                </div>
                            </div>
                        </div>

                        <div>
                            <div className="mb-5 flex flex-wrap items-center gap-2 text-xs uppercase tracking-[0.22em] text-white/45">
                                <span className="rounded-full border border-white/10 bg-white/5 px-3 py-1">Music Player</span>
                                {media.album && (
                                    <span className="rounded-full border border-white/10 bg-white/5 px-3 py-1">{media.album}</span>
                                )}
                            </div>

                            <h1 className="max-w-2xl text-3xl font-semibold leading-tight text-white sm:text-4xl">
                                {media.title ?? 'Sem título'}
                            </h1>

                            {media.artist && (
                                <p className="mt-3 text-lg text-white/70">{media.artist}</p>
                            )}

                            {media.description && (
                                <p className="mt-4 max-w-2xl text-sm leading-7 text-white/55 sm:text-base">
                                    {media.description}
                                </p>
                            )}

                            {audioError && (
                                <p className="mt-4 rounded-2xl border border-red-300/20 bg-red-400/10 px-4 py-3 text-sm text-red-200">
                                    {audioError}
                                </p>
                            )}

                            <AudioControls />
                        </div>
                    </div>
                </div>

                <audio
                    ref={audioRef}
                    src={url}
                    preload="metadata"
                    playsInline
                    onLoadedMetadata={onAudioLoadedMetadata}
                    onTimeUpdate={onAudioTimeUpdate}
                    onEnded={() => setPlaying(false)}
                    onError={() => {
                        setPlaying(false);
                        setAudioError('Arquivo de audio indisponivel ou URL invalida.');
                    }}
                />
            </div>
        );
    }

    // ══════════════════════════════════════════════════════════════════
    // VIDEO LAYOUT
    // ══════════════════════════════════════════════════════════════════
    return (
        <div
            ref={containerRef}
            className="relative w-full min-h-screen bg-black flex flex-col items-center justify-center select-none"
            onMouseMove={resetHideTimer}
            onTouchStart={resetHideTimer}
        >
            {/* Video */}
            <div
                className="relative w-full"
                style={{ maxHeight: '100vh', aspectRatio: '16/9' }}
                onClick={() => {
                    if (!isYT) setPlaying(v => !v);
                }}
            >
                {isYT ? (
                    <iframe
                        src={url}
                        title={media.title ?? 'YouTube player'}
                        className="w-full h-full"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        referrerPolicy="strict-origin-when-cross-origin"
                        allowFullScreen
                    />
                ) : (
                    <>
                        <ReactPlayer
                            ref={playerRef}
                            url={url}
                            playing={playing}
                            muted={muted}
                            volume={volume}
                            width="100%"
                            height="100%"
                            onReady={() => setReady(true)}
                            onDuration={setDuration}
                            onProgress={onProgress}
                            config={{
                                file: {
                                    forceHLS: Boolean(media.hls_url),
                                    attributes: { playsInline: true },
                                },
                            }}
                        />

                        {/* Overlay controls */}
                        <Controls overlay />

                        {/* Big play icon on pause */}
                        {!playing && ready && (
                            <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <div className="w-20 h-20 rounded-full bg-black/50 flex items-center justify-center">
                                    <Play size={40} fill="white" className="text-white ml-1" />
                                </div>
                            </div>
                        )}
                    </>
                )}
            </div>

            {/* Title below video (outside fullscreen) */}
            {!fullscreen && (
                <div className="w-full max-w-4xl px-4 pt-4 pb-8">
                    <h1 className="text-white text-xl font-bold leading-snug">{media.title}</h1>
                    {media.description && (
                        <p className="text-white/50 text-sm mt-2 line-clamp-2">{media.description}</p>
                    )}
                </div>
            )}
        </div>
    );
}
