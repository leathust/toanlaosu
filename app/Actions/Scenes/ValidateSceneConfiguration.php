<?php

namespace App\Actions\Scenes;

use Illuminate\Validation\ValidationException;

class ValidateSceneConfiguration
{
    /**
     * @param  array<string, mixed>  $config
     * @param  array<int, mixed>  $allowedCommands
     * @return array{type:string,parameters:array<string,mixed>}
     */
    public function execute(array $config, array $allowedCommands): array
    {
        $type = $config['type'] ?? null;
        if (! is_string($type) || ! in_array($type, ['radar', 'ant', 'graph', 'surface'], true)) {
            throw ValidationException::withMessages(['config.type' => 'Scene type không nằm trong allow-list.']);
        }

        $commands = array_values(array_filter($allowedCommands, static fn (mixed $command): bool => is_string($command)));
        $invalidCommands = array_diff($commands, ['play', 'pause', 'seek', 'reset', 'parameter.update']);
        if ($invalidCommands !== []) {
            throw ValidationException::withMessages(['allowed_commands' => 'Scene chứa command không hợp lệ.']);
        }

        return [
            'type' => $type,
            'parameters' => is_array($config['parameters'] ?? null) ? $config['parameters'] : [],
        ];
    }
}
