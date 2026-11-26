export const sceneTypes = ['radar', 'ant', 'graph', 'surface'] as const;
export const sceneCommands = ['play', 'pause', 'seek', 'reset', 'parameter.update'] as const;

export type SceneType = (typeof sceneTypes)[number];
export type SceneCommand = (typeof sceneCommands)[number];

export interface SceneConfig {
    type: SceneType;
    parameters: Record<string, unknown>;
}

export interface PersistedScene {
    public_id: string;
    name: string;
    scene_key: string;
    schema_version: string;
    config: SceneConfig;
    steps: Array<Record<string, unknown>>;
    allowed_commands: SceneCommand[];
    fallback: { disk: string | null; path: string | null } | null;
}

export function isSceneType(value: unknown): value is SceneType {
    return typeof value === 'string' && (sceneTypes as readonly string[]).includes(value);
}

export function isSceneCommand(value: unknown): value is SceneCommand {
    return typeof value === 'string' && (sceneCommands as readonly string[]).includes(value);
}

export function validateScenePayload(payload: PersistedScene): PersistedScene {
    if (!isSceneType(payload.config?.type) || payload.allowed_commands.some((command) => !isSceneCommand(command))) {
        throw new Error('Invalid persisted scene payload');
    }

    return payload;
}
