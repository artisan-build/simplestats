@php
use App\Services\StatsService;
use \Illuminate\Support\Str;
use \Illuminate\Support\Number;

$enabledVisitors = $trackingSettings['visitors'] ?? false;
$enabledUsers = $trackingSettings['users'] ?? false;
$enabledPayments = $trackingSettings['payments'] ?? false;

$header = '|Name';
if ($enabledVisitors) $header .= '|Visitors';
if ($enabledUsers) $header .= '|Regs';
if ($enabledUsers || $enabledVisitors) $header .= '|Conv.';
if ($enabledUsers) $header .= '|'.$frequency->title()[0].'AU';
if ($enabledPayments) $header .= '|Revenue';
$header .= '|';

$separator = '';
$columnCount = substr_count($header, '|') - 1;
$separatorParts = array_fill(0, $columnCount, '-:');
$separatorParts[0] = ':-';
$separator = '|' . implode('|', $separatorParts) . '|';

$th = $header . PHP_EOL . $separator;
//dd($statsGroups);
$tables = [];
foreach ($statsGroups as $key => $statsGroup) {
    if (empty($statsGroup['stats'])) continue;

    if (!isset($tables[$key])) {
        $tables[$key] = $th . PHP_EOL;
    }

    foreach ($statsGroup['stats'] as $stats) {
        $name = $stats['name'];
        if (Str::endsWith($name, '/')) {
            $name = '/' . Str::substr($name, 0, -1);
        }

        $td = '|'.Str::reverse(Str::limit(Str::reverse($name), 25));
        if ($enabledVisitors) $td .= '|'.($stats['visitors'] >= 1000 ? Number::abbreviate($stats['visitors'], 2) : $stats['visitors']);
        if ($enabledUsers) $td .= '|'.($stats['reg'] >= 1000 ? Number::abbreviate($stats['reg'], 2) : $stats['reg']);
        if ($enabledUsers || $enabledVisitors) $td .= '|'.Number::percentage($stats['cr'], 2);
        if ($enabledUsers) $td .= '|'.($stats['dau'] >= 1000 ? Number::abbreviate($stats['dau'], 2) : $stats['dau']);
        if ($enabledPayments) $td .= '|'.Number::currency($stats['net']/100, in: $currency, precision: 2);

        $tables[$key] .= $td .'|'. PHP_EOL;
    }
}
@endphp

@component('mail::message')
# {{ $frequency->title() }} report

## {{ $timeRange }}<br>
<br>
Here is your {{ $frequency->value }} report for <strong>{{ $projectName }}</strong>:<br>
<br>
<br>
@foreach($tables as $key => $table)
Top <strong>{{ $statsGroups[$key]['descr'] }}</strong>:
@component('mail::table')
{{ $table }}
@endcomponent
<br>
@endforeach

<a href="{{ route('notifications.index') }}" target="_blank">Change notification settings</a>
@endcomponent
