import type { SceneCommand, SceneType } from './sceneSchema';

export interface SceneState {
    type: SceneType;
    progress: number;
    playing: boolean;
}

export function createSceneController(type: SceneType): {
    state: SceneState;
    play: () => void;
    pause: () => void;
    seek: (progress: number) => void;
    reset: () => void;
    canRun: (command: SceneCommand) => boolean;
} {
    const state: SceneState = { type, progress: 0, playing: false };

    return {
        state,
        play: () => { state.playing = true; },
        pause: () => { state.playing = false; },
        seek: (progress) => { state.progress = Math.min(1, Math.max(0, progress)); },
        reset: () => { state.playing = false; state.progress = 0; },
        canRun: (command) => command === 'play' || command === 'pause' || command === 'seek' || command === 'reset' || command === 'parameter.update',
    };
}
