import { describe, expect, it } from 'vitest';
import { createSceneController } from '../Scenes/sceneController';
import { validateScenePayload } from '../Scenes/sceneSchema';

describe('scene runtime contract', () => {
    it('clamps seek and resets animation state', () => {
        const controller = createSceneController('radar');
        controller.play();
        controller.seek(2);
        expect(controller.state).toMatchObject({ playing: true, progress: 1 });
        controller.reset();
        expect(controller.state).toMatchObject({ playing: false, progress: 0 });
    });

    it('rejects invalid persisted scene payloads', () => {
        expect(() => validateScenePayload({
            public_id: 'scene', name: 'Scene', scene_key: 'unknown', schema_version: '1.0',
            config: { type: 'invalid' as never, parameters: {} }, steps: [], allowed_commands: ['play'], fallback: null,
        })).toThrow('Invalid persisted scene payload');
    });
});
