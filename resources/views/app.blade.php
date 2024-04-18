<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="min-h-screen">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <meta name="description" content="The missing statistics tool for your Laravel application. A lightweight and GDPR-compliant statistics tool for Laravel, quickly reveals marketing ROI and user conversion with a very simple setup.">

        <meta property="og:title" content="{{ config('app.name', 'Laravel') }}">
        <meta property="og:description" content="The missing statistics tool for your Laravel application. A lightweight and GDPR-compliant statistics tool for Laravel, quickly reveals marketing ROI and user conversion with a very simple setup.">
        <meta property="og:image" content="{{ asset('/images/screenshot.png') }}">
        <meta property="og:url" content="{{ asset('') }}">
        <meta property="og:type" content="website">

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
        @inertia

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
    </body>
</html>
