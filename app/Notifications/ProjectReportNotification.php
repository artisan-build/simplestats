<?php

namespace App\Notifications;

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Enums\UserNotificationFrequency;
use App\Models\Team;
use App\Models\UserNotification;
use App\Services\StatsAggregationsService;
use App\Services\StatsDetailsService;
use App\Services\StatsService;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * currently we can't use ShouldQueue here because the notification is already triggered by a queued job,
 * and we would get issues with the unserialization of the StatsPayment model, cause of missing team context
 */
class ProjectReportNotification extends Notification
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
        auth()->setUser($this->notification->user);

        $projectName = $this->notification->notifiable->name;

        $subject = '📊 '.config('app.name').' '.
            $this->notification->frequency->title().' '.
            'Report for '.
            $projectName;

        $mail = (new MailMessage)
            ->subject($subject)
            ->markdown('emails.project-report', [
                'frequency' => $this->notification->frequency,
                'timeRange' => $this->getTimeRange(),
                'projectName' => $projectName,
                'trackingSettings' => $this->notification->settings['tracking'],
                'currency' => $this->team->currency,
                'statsAll' => $this->getStats(),
                'statsGroups' => $this->getStatsGroups(),
            ]);

        $this->notification->update(['sent_at' => $this->time]);

        return $mail;
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
            rangeAmount: $range['amount'],
            rangeType: $range['type'],
            rangeStart: $range['start'],
            comparison: '0'
        );
    }

    private function getStats()
    {
        return current(app(StatsDetailsService::class)->getStats($this->getFilter()));
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
                'title' => $typeData['title'],
                'stats' => app(StatsAggregationsService::class)
                    ->getAggregatedGroupedData($filter, $typeData['type'], $sort, 5)
                    ->values()->toArray(),
            ];
        });
    }
}
