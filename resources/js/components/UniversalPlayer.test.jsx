import React, { forwardRef, useEffect, useImperativeHandle } from 'react';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('react-player', () => {
    const MockReactPlayer = forwardRef(function MockReactPlayer(props, ref) {
        useImperativeHandle(ref, () => ({
            seekTo: vi.fn(),
        }));

        useEffect(() => {
            props.onReady?.();
            props.onDuration?.(120);
            props.onProgress?.({ played: 0.25, playedSeconds: 30 });
        }, []);

        return <div data-testid="mock-react-player" />;
    });

    return {
        default: MockReactPlayer,
    };
});

import UniversalPlayer from './UniversalPlayer';

describe('UniversalPlayer audio seek', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('updates the audio progress UI when skipping forward', async () => {
        render(
            <UniversalPlayer
                media={{
                    media_type: 'audio',
                    source: 'local_audio',
                    media_path: '/storage/music/example.wav',
                    title: 'Example track',
                    artist: 'Example artist',
                }}
            />,
        );

        const skipForwardButton = await screen.findByLabelText('+10s');
        fireEvent.click(skipForwardButton);

        await waitFor(() => {
            expect(screen.getAllByText('0:40').length).toBeGreaterThan(0);
        });
    });
});