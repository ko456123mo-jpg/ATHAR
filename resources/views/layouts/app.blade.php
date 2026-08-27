<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ATHAR') }} — منصة التحقيقات الرقمية</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="athar-shell antialiased">
        <div class="min-h-screen lg:flex">
            @include('layouts.navigation')
            <main class="min-w-0 flex-1 p-5 lg:p-9">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
