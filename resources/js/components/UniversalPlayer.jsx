import React, { useState, useRef } from 'react';
import ReactPlayer from 'react-player';
import { Play, Pause, Volume2, VolumeX, Music } from 'lucide-react';

/**
 * Resolve the playback URL from a media object.
 *
 * Priority:
 *   1. HLS stream (video/hls source)
 *   2. Direct media file path (local_audio)
 *   3. YouTube embed URL (youtube source)
 */
function resolveUrl(media) {
    if (!media) return null;
    if (media.hls_url) return media.hls_url;
    if (media.media_path) return media.media_path;
    if (media.embed_url) return media.embed_url;
    return null;
}

export default function UniversalPlayer({ media }) {
    const [playing, setPlaying] = useState(false);
    const [muted, setMuted] = useState(false);
    const [ready, setReady] = useState(false);
    const playerRef = useRef(null);

    const url = resolveUrl(media);
    const isAudio = media?.media_type === 'audio';
    const hasThumbnail = Boolean(media?.thumbnail_url);

    if (!media || !url) {
        return (
            <div className="flex items-center justify-center w-full h-screen bg-black">
                <p className="text-white text-3xl font-bold">No media selected.</p>
            </div>
        );
    }

    return (
        <div className="flex flex-col items-center justify-center w-full min-h-screen bg-black select-none">

            {/* ── Artwork / Video ───────────────────────────────────────── */}
            <div className="relative w-full max-w-3xl aspect-video bg-neutral-900 rounded-2xl overflow-hidden shadow-2xl">

                {isAudio ? (
                    /* Audio: show album art or a fallback icon */
                    <div className="flex items-center justify-center w-full h-full">
                        {hasThumbnail ? (
                            <img
                                src={media.thumbnail_url}
                                alt={media.title}
                                className="object-cover w-full h-full"
                            />
                        ) : (
                            <Music className="text-neutral-600" size={120} strokeWidth={1} />
                        )}
                    </div>
                ) : (
                    /* Video: react-player fills the container */
                    <ReactPlayer
                        ref={playerRef}
                        url={url}
                        playing={playing}
                        muted={muted}
                        width="100%"
                        height="100%"
                        onReady={() => setReady(true)}
                        config={{
                            file: {
                                forceHLS: Boolean(media.hls_url),
                                attributes: { playsInline: true },
                            },
                        }}
                    />
                )}

                {/* Audio: hidden ReactPlayer to drive playback */}
                {isAudio && (
                    <ReactPlayer
                        ref={playerRef}
                        url={url}
                        playing={playing}
                        muted={muted}
                        width="0"
                        height="0"
                        onReady={() => setReady(true)}
                    />
                )}
            </div>

            {/* ── Metadata ──────────────────────────────────────────────── */}
            <div className="mt-6 text-center px-4 max-w-2xl">
                <h1 className="text-white text-3xl font-extrabold leading-tight truncate">
                    {media.title ?? 'Untitled'}
                </h1>
                {(media.artist || media.album) && (
                    <p className="text-neutral-400 text-xl mt-1">
                        {[media.artist, media.album].filter(Boolean).join(' · ')}
                    </p>
                )}
            </div>

            {/* ── Kiosk Controls ────────────────────────────────────────── */}
            <div className="flex items-center gap-8 mt-8">

                {/* Mute toggle */}
                <button
                    onClick={() => setMuted(v => !v)}
                    className="flex items-center justify-center w-20 h-20 rounded-full bg-neutral-800 active:bg-neutral-700 transition-colors"
                    aria-label={muted ? 'Unmute' : 'Mute'}
                >
                    {muted
                        ? <VolumeX className="text-white" size={36} />
                        : <Volume2 className="text-white" size={36} />
                    }
                </button>

                {/* Play / Pause — oversized touch target */}
                <button
                    onClick={() => setPlaying(v => !v)}
                    disabled={!ready}
                    className="flex items-center justify-center w-32 h-32 rounded-full bg-white active:scale-95 transition-transform disabled:opacity-40"
                    aria-label={playing ? 'Pause' : 'Play'}
                >
                    {playing
                        ? <Pause className="text-black" size={56} fill="black" />
                        : <Play  className="text-black" size={56} fill="black" />
                    }
                </button>

                {/* Spacer to balance the mute button */}
                <div className="w-20" />
            </div>
        </div>
    );
}
