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
    const containerRef = useRef(null);
    const hideTimer   = useRef(null);

    const isAudio = media?.media_type === 'audio';
    const isYT    = media?.source === 'youtube';
    const resolvedUrl = resolveUrl(media);
    const url = isYT ? normalizeYouTubeEmbedUrl(resolvedUrl) : resolvedUrl;

    // ── Keyboard shortcuts ──────────────────────────────────────────────
    useEffect(() => {
        function onKey(e) {
            if (e.target.tagName === 'INPUT') return;
            if (e.code === 'Space')       { e.preventDefault(); setPlaying(v => !v); }
            if (e.code === 'KeyM')        setMuted(v => !v);
            if (e.code === 'ArrowRight')  playerRef.current?.seekTo(played * duration + 10, 'seconds');
            if (e.code === 'ArrowLeft')   playerRef.current?.seekTo(Math.max(0, played * duration - 10), 'seconds');
        }
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [played, duration]);

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
        setPlayed(parseFloat(e.target.value));
    }
    function onSeekMouseDown() { setSeeking(true); }
    function onSeekMouseUp(e) {
        setSeeking(false);
        playerRef.current?.seekTo(parseFloat(e.target.value));
    }
    function onProgress(state) {
        if (!seeking) setPlayed(state.played);
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
                            onClick={() => playerRef.current?.seekTo(Math.max(0, played * duration - 10), 'seconds')}
                            className="text-white/70 hover:text-white transition-colors"
                            aria-label="-10s"
                        >
                            <SkipBack size={20} />
                        </button>
                    )}

                    <button
                        onClick={() => setPlaying(v => !v)}
                        disabled={!ready}
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
                            onClick={() => playerRef.current?.seekTo(Math.min(1, played + 10 / duration), 'fraction')}
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

    // ══════════════════════════════════════════════════════════════════
    // AUDIO LAYOUT
    // ══════════════════════════════════════════════════════════════════
    if (isAudio) {
        return (
            <div
                className="relative flex flex-col items-center justify-center w-full min-h-screen bg-neutral-950 overflow-hidden select-none"
                style={media.thumbnail_url ? {
                    backgroundImage: `url(${media.thumbnail_url})`,
                    backgroundSize: 'cover',
                    backgroundPosition: 'center',
                } : {}}
            >
                {/* Blur overlay */}
                <div className="absolute inset-0 bg-black/60 backdrop-blur-2xl" />

                <div className="relative z-10 flex flex-col items-center w-full max-w-sm px-6">
                    {/* Album art disc */}
                    <div
                        className="relative w-56 h-56 rounded-full shadow-2xl overflow-hidden mb-8 border-4 border-white/10"
                        style={{
                            animation: playing ? 'spin 8s linear infinite' : 'none',
                        }}
                    >
                        {media.thumbnail_url ? (
                            <img
                                src={media.thumbnail_url}
                                alt={media.title}
                                className="w-full h-full object-cover"
                            />
                        ) : (
                            <div className="w-full h-full bg-neutral-800 flex items-center justify-center">
                                <Music size={72} className="text-neutral-600" strokeWidth={1} />
                            </div>
                        )}
                        {/* Center hole */}
                        <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div className="w-10 h-10 rounded-full bg-neutral-950 border-2 border-white/10" />
                        </div>
                    </div>

                    {/* Metadata */}
                    <h1 className="text-white text-2xl font-bold text-center leading-tight mb-1">
                        {media.title ?? 'Sem título'}
                    </h1>
                    {media.artist && (
                        <p className="text-white/60 text-base text-center mb-0.5">{media.artist}</p>
                    )}
                    {media.album && (
                        <p className="text-white/40 text-sm text-center">{media.album}</p>
                    )}

                    <div className="mt-8 w-full">
                        <Controls />
                    </div>
                </div>

                {/* Hidden audio player */}
                <ReactPlayer
                    ref={playerRef}
                    url={url}
                    playing={playing}
                    muted={muted}
                    volume={volume}
                    width="0" height="0"
                    onReady={() => setReady(true)}
                    onDuration={setDuration}
                    onProgress={onProgress}
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
