@component('mail::message')
    <h1>Update Announcement - {{ now()->format('Y-m-d') }}</h1>
    <br>
    Hello {{ $user->name }},<br>
    <br>
    Yesterday we released <b>SimpleStats 2.0</b> with a bunch of new features.<br>
    <br>
    <b>Important:</b> make sure to update the <i><a href="https://github.com/simplestats-io/laravel-client">simplestats-io/laravel-client</a></i> composer client package to version 2!<br>
    <br>
    <h2>What's changed:</h2>
    <ul>
        <li>New improved user interface</li>
        <li>Unique Visitors tracking</li>
        <li>Conversion Rate (percentage of visitors who sign up for the application)</li>
        <li>Referer tracking (moved to own category)</li>
        <li>Entry Pages tracking</li>
        <li>Locations tracking (Countries, Regions and Cities)</li>
        <li>Device tracking (Browser, OS and Size)</li>
        <li>Aggregated quick-view panels for referrers, UTMs, pages, locations and devices.</li>
    </ul>
    <br>
    The good news is that these metrics are much more accurate than client-side approaches,
    because they are made directly on the server. This means they are not affected by browser settings,
    ad blockers, or JavaScript issues that can hinder frontend tracking.
    As a result, backend tracking provides a more reliable and comprehensive view of user interactions and behavior.<br>
    <br>
    Again, its <b>important</b> to update the <i><a href="https://github.com/simplestats-io/laravel-client">simplestats-io/laravel-client</a></i> composer client package to version 2!<br>
    <br>
    Happy analysing,<br>
    The SimpleStats-Team<br>
    <br>
    PS: Feel free to reach out to us at <a href="mailto:{{ config('app.app_mail') }}">{{ config('app.app_mail') }}</a> if you have any questions!
<br>

@endcomponent
