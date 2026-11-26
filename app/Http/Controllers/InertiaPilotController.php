<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class InertiaPilotController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Ui/InertiaPilot', [
            'pilot' => [
                'title' => 'Inertia foundation pilot',
                'status' => 'ready-for-vertical-slice',
            ],
        ]);
    }
}
