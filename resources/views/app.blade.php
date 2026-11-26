<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @inertiaHead
        @vite(['resources/css/app.css', 'resources/js/app.ts'])
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-950 antialiased">
        @inertia
    </body>
</html>
