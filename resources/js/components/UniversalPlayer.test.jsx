import React, { forwardRef, useEffect, useImperativeHandle } from 'react';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const seekToMock = vi.fn();

vi.mock('react-player', () => {
    const MockReactPlayer = forwardRef(function MockReactPlayer(props, ref) {
        useImperativeHandle(ref, () => ({
            seekTo: seekToMock,
        }));

        useEffect(() => {
            props.onReady?.();
            props.onDuration?.(120);
            props.onProgress?.({ played: 0.25, playedSeconds: 30 });
        }, [props]);

        return <div data-testid="mock-react-player" />;
    });

    return {
        default: MockReactPlayer,
    };
});

import UniversalPlayer from './UniversalPlayer';

describe('UniversalPlayer audio seek', () => {
    beforeEach(() => {
        seekToMock.mockReset();
    });

    it('seeks audio by seconds when the progress slider changes', async () => {
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

        const seekSlider = await screen.findByLabelText('Seek audio');

        fireEvent.mouseDown(seekSlider);
        fireEvent.change(seekSlider, { target: { value: '60' } });
        fireEvent.mouseUp(seekSlider);

        await waitFor(() => {
            expect(seekToMock).toHaveBeenLastCalledWith(60, 'seconds');
        });

        expect(seekToMock).not.toHaveBeenCalledWith(0, 'seconds');
    });
});