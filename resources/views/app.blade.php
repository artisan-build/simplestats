<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="min-h-screen">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <meta name="description" content="The missing statistics tool for your Laravel application. A server-side, GDPR compliant and 100% accurate statistics/analytics tool, that goes beyond simple counts of views and visits. Discover in-depth metrics like Registrations, Daily Active Users, New Paying Users, campaign ROI and much more!">

        <meta property="og:title" content="{{ config('app.name', 'Laravel') }}">
        <meta property="og:description" content="The missing statistics tool for your Laravel application. A server-side, GDPR compliant and 100% accurate statistics/analytics tool, that goes beyond simple counts of views and visits. Discover in-depth metrics like Registrations, Daily Active Users, New Paying Users, campaign ROI and much more!">
        <meta property="og:image" content="{{ asset('/images/screenshot.png') }}">
        <meta property="og:url" content="{{ asset('') }}">
        <meta property="og:type" content="website">

        {{-- change this also in docs vitepress config.js --}}
        <link rel="canonical" href="https://simplestats.io{{ request()->getPathInfo() }}">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Icons -->
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="manifest" href="/site.webmanifest">

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans min-h-screen bg-white dark:bg-gray-900 font-normal text-gray-950 antialiased dark:bg-gray-950 dark:text-white">
        @impersonating($guard = null)
            <div class="w-full bg-red-200 px-20 py-2 text-center">
                Attention: you are impersonating user <span class="font-bold">{{ auth()->user()->name }}</span>
                <a class="rounded-md bg-gray-100 hover:bg-gray-200 px-3 py-1 border border-gray-400 ml-2" href="{{ route('impersonate.leave') }}">Leave</a>
            </div>
        @endImpersonating

        @inertia

        @if(app()->isProduction())
            <!--Start of Tawk.to Script-->
            <script type="text/javascript">
                var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
                (function(){
                    var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
                    s1.async=true;
                    s1.src='https://embed.tawk.to/6601f04ea0c6737bd124880f/1hprp5d8g';
                    s1.charset='UTF-8';
                    s1.setAttribute('crossorigin','*');
                    s0.parentNode.insertBefore(s1,s0);
                })();
            </script>
            <!--End of Tawk.to Script-->
        @endif
    </body>
</html>
