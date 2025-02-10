@component('mail::message')
    <h1>Update Announcement - {{ now()->format('Y-m-d') }}</h1>
    <br>
    Hello {{ $user->name }},<br>
    <br>
    Today we released <b>SimpleStats 4.0 - "Dark Mode & Performance"</b> with a bunch of new features and improvements.<br>
    <br>
    <h2>Here’s what’s new:</h2>
    <ul>
        <li><b>Massive performance improvements</b> – it should be much more fun now to navigate through your dashboard!</li>
        <li><b>Optimized API performance</b> – improved response times for API endpoints.</li>
        <li><b>Dark mode</b> is finally here!</li>
        <li><b>New date presets</b> – quickly filter by "Last 12 Weeks" or "Last 6 Months".</li>
        <li><b>UI/UX enhancements</b> for a better experience.</li>
        <li><b>Various bug fixes</b> for increased stability.</li>
        <li><b>FAQ</b></li>
    </ul>
    <br>
    Happy analysing,<br>
    The SimpleStats-Team
<br>

@endcomponent
