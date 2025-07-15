@php
use App\Services\StatsService;
use \Illuminate\Support\Str;
use \Illuminate\Support\Number;

$enabledVisitors = $trackingSettings['visitors'] ?? false;
$enabledUsers = $trackingSettings['users'] ?? false;
$enabledPayments = $trackingSettings['payments'] ?? false;

// ===== STATS ALL

$statsHeader = '';
if ($enabledVisitors) $statsHeader .= '|Visitors';
if ($enabledUsers) $statsHeader .= '|Registrations';
if ($enabledUsers || $enabledVisitors) $statsHeader .= '|Conversion';
if ($enabledUsers) $statsHeader .= '|'.$frequency->title()[0].'AU';
if ($enabledPayments) $statsHeader .= '|Revenue';
$statsHeader .= '|';

$statsSeparator = '';
$columnCount = substr_count($statsHeader, '|') - 1;
$statsSeparatorParts = array_fill(0, $columnCount, ':-:');
$statsSeparator = '|' . implode('|', $statsSeparatorParts) . '|';

$statsTH = $statsHeader . PHP_EOL . $statsSeparator;

$td = '';
if ($enabledVisitors) $td .= '|'.($statsAll['pd_visitor'] >= 1000 ? Number::abbreviate($statsAll['pd_visitor'], 2) : $statsAll['pd_visitor']);
if ($enabledUsers) $td .= '|'.($statsAll['pd_reg'] >= 1000 ? Number::abbreviate($statsAll['pd_reg'], 2) : $statsAll['pd_reg']);
if ($enabledUsers || $enabledVisitors) $td .= '|'.Number::percentage($statsAll['pd_cr'], 2);
if ($enabledUsers) $td .= '|'.($statsAll['pd_dau'] >= 1000 ? Number::abbreviate($statsAll['pd_dau'], 2) : $statsAll['pd_dau']);
if ($enabledPayments) $td .= '|'.Number::currency($statsAll['pd_net']/100, in: $currency, precision: 2);

$statsTable = $statsTH. PHP_EOL.$td .'|'. PHP_EOL;

// ===== STATS GROUPS

$statsGroupsHeader = '|Name';
if ($enabledVisitors) $statsGroupsHeader .= '|Visitors';
if ($enabledUsers) $statsGroupsHeader .= '|Regs';
if ($enabledUsers || $enabledVisitors) $statsGroupsHeader .= '|Conv.';
if ($enabledUsers) $statsGroupsHeader .= '|'.$frequency->title()[0].'AU';
if ($enabledPayments) $statsGroupsHeader .= '|Revenue';
$statsGroupsHeader .= '|';

$statsGroupsSeparator = '';
$columnCount = substr_count($statsGroupsHeader, '|') - 1;
$statsGroupsSeparatorParts = array_fill(0, $columnCount, '-:');
$statsGroupsSeparatorParts[0] = ':-';
$statsGroupsSeparator = '|' . implode('|', $statsGroupsSeparatorParts) . '|';

$statsGroupsTH = $statsGroupsHeader . PHP_EOL . $statsGroupsSeparator;

$statsGroupsTables = [];
foreach ($statsGroups as $key => $statsGroup) {
    if (empty($statsGroup['stats'])) continue;

    if (!isset($statsGroupsTables[$key])) {
        $statsGroupsTables[$key] = $statsGroupsTH . PHP_EOL;
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

        $statsGroupsTables[$key] .= $td .'|'. PHP_EOL;
    }
}
@endphp

@component('mail::message')
# {{ $frequency->title() }} report

## {{ $timeRange }}<br>
<br>
📬 Here is your {{ $frequency->value }} report for {{ $projectName }}:<br>
@component('mail::table')
{{ $statsTable }}
@endcomponent
<br>
@foreach($statsGroupsTables as $key => $table)
📊 Breakdown by <strong>{{ $statsGroups[$key]['title'] }}</strong>:
@component('mail::table')
{{ $table }}
@endcomponent
<br>
@endforeach

<a href="{{ route('notifications.index') }}" target="_blank">Change notification settings</a>
@endcomponent
