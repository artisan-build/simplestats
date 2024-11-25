@component('mail::message')
    <h1>Update Announcement - {{ now()->format('Y-m-d') }}</h1>
    <br>
    Hello {{ $user->name }},<br>
    <br>
    Today we released <b>SimpleStats 3.0</b> with a bunch of new features.<br>
    <br>
    <h2>The latest changes include the following improvements:</h2>
    <ul>
        <li><b>Previous</b> period and <b>Year over year comparison</b></li>
        <li>You can now zoom into the graph data, by clicking the data points</li>
        <li>We make use of the new defer function, which makes the need of a <b>queue optional</b>, which makes the installation setup even easier</li>
        <li>Payments can now not only be <b>associated</b> with users but also with visitors, if your application does not have any users at all.</li>
        <li>Therefor we added a new KPI on our dashboard: <b>ARPV</b> (Average Revenue per Visitor)</li>
        <li>Implemented <b>bots filtering</b> right into the client package which drastically reduces api requests to our servers and improves performance</li>
        <li>Browser/Bot detection should now be much <b>faster</b> cause of the use of <b>caching</b></li>
        <li>Added <b>social login</b> (Google and Github)</li>
        <li>Support for <b>UUID</b> and <b>ULID</b></li>
        <li><b>Timezone</b> fixes</li>
        <li>Lots of small <b>bugfixes</b> and <b>improvements</b></li>
    </ul>
    <br>
    Happy analysing,<br>
    The SimpleStats-Team<br>
    <br>
    PS: It's BlackWeek! Use this code at checkout until 2. Dec to get 50% off all plans: <b>BLACKWEEK2024</b>
<br>

@endcomponent
