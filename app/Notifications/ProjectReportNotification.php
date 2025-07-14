<?php

namespace App\Notifications;

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Enums\UserNotificationFrequency;
use App\Models\Team;
use App\Models\UserNotification;
use App\Services\StatsAggregationsService;
use App\Services\StatsService;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class ProjectReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public UserNotification $notification,
        public CarbonInterface $time,
        public Team $team,
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $projectName = class_basename($this->notification->notifiable->name);

        $subject = config('app.name').' '.
            $this->notification->frequency->title().' '.
            $projectName.' '.
            'Report';

        //TODO only send notification mail if even any data is found! (skip demo stuff)

        $statsGroups = $this->getStatsGroups();

        return (new MailMessage)
            ->subject($subject)
            ->markdown('emails.project-report', [
                'frequency' => $this->notification->frequency,
                'timeRange' => $this->getTimeRange(),
                'projectName' => $projectName,
                'trackingSettings' => $this->notification->settings['tracking'],
                'currency' => $this->team->currency,
                'statsGroups' => $statsGroups,
            ]);
    }

    private function getTimeRange(): string
    {
        return match ($this->notification->frequency) {
            UserNotificationFrequency::DAILY => $this->time->subDay()->format('M jS'),
            UserNotificationFrequency::WEEKLY => $this->time->subWeek()->startOfWeek()->format('M jS').' - '.$this->time->subWeek()->endOfWeek()->format('M jS'),
            UserNotificationFrequency::MONTHLY => $this->time->subMonth()->startOfMonth()->format('M jS').' - '.$this->time->subMonth()->endOfMonth()->format('M jS'),
            default => '',
        };
    }

    private function getRange(): array
    {
        return match ($this->notification->frequency) {
            UserNotificationFrequency::DAILY => [
                'amount' => 1,
                'type' => StatsRangeType::DAY,
                'start' => StatsRangeType::DAY->rangeStart($this->time->subDay()),
            ],
            UserNotificationFrequency::WEEKLY => [
                'amount' => 1,
                'type' => StatsRangeType::WEEK,
                'start' => StatsRangeType::WEEK->rangeStart($this->time->subWeek()),
            ],
            UserNotificationFrequency::MONTHLY => [
                'amount' => 1,
                'type' => StatsRangeType::MONTH,
                'start' => StatsRangeType::MONTH->rangeStart($this->time->subMonth()),
            ],
            default => [],
        };
    }

    private function getFilter(): StatsFilterDto
    {
        $range = $this->getRange();

        return new StatsFilterDto(
            projectId: $this->notification->notifiable_id,
            instanceId: 0,
            rangeAmount: $range['amount'],
            rangeType: $range['type'],
            rangeStart: $range['start'],
            comparison: '0'
        );
    }

    public function getStatsGroups(): Collection
    {
        $filter = $this->getFilter();

        $sort = match (true) {
            $this->notification->settings['tracking']['payments'] ?? false => 'net',
            $this->notification->settings['tracking']['users'] ?? false => 'reg',
            default => 'visitors',
        };

        return collect($this->notification->settings['types'])->map(function ($type) use ($filter, $sort) {
            $typeData = StatsService::getStatsTypesCamelFlatMap()->keyBy('model')[$type];

            return [
                'descr' => $typeData['descr'],
                'stats' => app(StatsAggregationsService::class)
                    ->getAggregatedGroupedData($filter, $typeData['type'], $sort, 5)
                    ->values()->toArray(),
            ];
        });
    }
}
