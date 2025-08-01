@component('mail::message')
    <h1>Update Announcement - {{ now()->format('Y-m-d') }}</h1>
    <br>
    Hello {{ $user->name }},<br>
    <br>
    Today we released <b>SimpleStats 5.0</b> with a bunch of new features and improvements.<br>
    <br>
    <h2>Here's what's new:</h2>
    <ul>
        <li><b>Improved performance and scalability</b> - especially noticeable when exploring large time ranges</li>
        <li><b>Event-based pricing</b> (no more total limit blocks for visitors, registrations, etc.)</li>
        <li><b>Email reports</b> for projects (configureable under User -> Notifications)</li>
        <li><b>Instant project alerts</b> on new user or payment (disabled by default - ideal for early-stage or smaller teams)</li>
        <li><b>Dark mode improvements</b> and toggle in user profile</li>
        <li><b>Redesigned onboarding</b> and registration flow</li>
        <li>Dashboard remembers your <b>last selected filters</b> instead of always resetting to “last 7 days”</li>
        <li>Fixed edge cases in <b>DAU/WAU/MAU</b> calculation</li>
        <li><b>UI/UX improvements</b> throughout the app</li>
        <li><b>Mobile & responsive</b> improvements for better navigation on all devices</li>
        <li><b>Improved ingestion</b> performance on API stats endpoints</li>
        <li><b>Clickable charts</b> for detail view feature now also supported for week range types</li>
        <li><b>Many small bug fixes</b> and visual refinements</li>
        <li>Improved documentation</li>
    </ul>
    <br>
    Happy analysing,<br>
    The SimpleStats-Team
<br>

@endcomponent
