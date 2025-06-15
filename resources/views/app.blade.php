<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Tic Tac Toe Multiplayer') }}</title>

        <meta name="csrf-token" content="{{ csrf_token() }}">

        <meta name="description" content="A Laravel, Vue3 and Reverb Tic Tac Toe Multiplayer game project">
        <meta name="author" content="Lucas Silva">
        <meta name="robots" content="index, follow">
        <meta name="keywords" content="laravel, vuejs, reverb">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
