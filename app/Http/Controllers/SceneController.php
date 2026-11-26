<?php

namespace App\Http\Controllers;

use App\Actions\Scenes\ValidateSceneConfiguration;
use App\Http\Requests\SceneCommandRequest;
use App\Models\VisualScene;
use Illuminate\Http\JsonResponse;

class SceneController extends Controller
{
    public function show(VisualScene $visualScene, ValidateSceneConfiguration $validator): JsonResponse
    {
        $version = $visualScene->publishedVersion()->where('status', 'published')->firstOrFail();
        $config = $validator->execute($version->config, $version->allowed_commands ?? []);

        return response()->json([
            'data' => [
                'public_id' => $visualScene->public_id,
                'name' => $visualScene->name,
                'scene_key' => $visualScene->scene_key,
                'schema_version' => $version->schema_version,
                'config' => $config,
                'steps' => $version->steps ?? [],
                'allowed_commands' => $version->allowed_commands ?? [],
                'fallback' => $version->fallback_path ? ['disk' => $version->fallback_disk, 'path' => $version->fallback_path] : null,
            ],
        ]);
    }

    public function command(SceneCommandRequest $request, VisualScene $visualScene): JsonResponse
    {
        $version = $visualScene->publishedVersion()->where('status', 'published')->firstOrFail();
        abort_unless(in_array($request->string('command')->toString(), $version->allowed_commands ?? [], true), 422, 'Scene command không được phép.');

        return response()->json(['data' => ['command' => $request->string('command')->toString(), 'accepted' => true]]);
    }
}
