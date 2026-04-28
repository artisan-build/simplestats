<?php

namespace App\Services;

use Illuminate\Support\Collection;

class RefererService
{
    private Collection $topReferers;

    /**
     * Top referers whose subdomains should fold back to the canonical domain.
     * E.g. m.facebook.com, l.facebook.com, lm.facebook.com → facebook.com.
     * Explicitly NOT included: google.com, bing.com (subdomains carry meaning,
     * e.g. mail.google.com vs drive.google.com vs accounts.google.com).
     *
     * @var array<int, string>
     */
    private array $foldSubdomainsFor = [
        'reddit.com',
        'facebook.com',
        'instagram.com',
        'threads.com',
        'youtube.com',
        'tiktok.com',
        'linkedin.com',
        'baidu.com',
        'vk.com',
        'twitter.com',
        'x.com',
        'pinterest.com',
        'tumblr.com',
        'snapchat.com',
        'duckduckgo.com',
        'search.yahoo.com',
        'qwant.com',
        'ecosia.org',
        'startpage.com',
        'yandex.ru',
    ];

    /**
     * Hard aliases applied after the main lookup. Handles short links
     * (youtu.be, t.co), legacy domains (vkontakte.ru → vk.com),
     * Android app package identifiers, and threads.net consolidation.
     *
     * @var array<string, string>
     */
    private array $aliases = [
        'youtu.be' => 'youtube.com',
        't.co' => 'x.com',
        'twitter.com' => 'x.com',
        'threads.net' => 'threads.com',
        'l.threads.net' => 'threads.com',
        'vkontakte.ru' => 'vk.com',
        'com.google.android.googlequicksearchbox' => 'google.com',
        'com.google.android.youtube' => 'youtube.com',
        'com.linkedin.android' => 'linkedin.com',
        'com.reddit.frontpage' => 'reddit.com',
        'com.laurencedawson.reddit_sync' => 'reddit.com',
        'org.telegram.messenger.web' => 'telegram.org',
    ];

    public function __construct()
    {
        $this->topReferers = collect([
            'google.com' => 'Google',
            'facebook.com' => 'Facebook',
            'youtube.com' => 'YouTube',
            'instagram.com' => 'Instagram',
            'threads.com' => 'Threads',
            'bing.com' => 'Bing',
            'search.yahoo.com' => 'Yahoo',
            'search.brave.com' => 'Brave',
            'twitter.com' => 'Twitter',
            'x.com' => 'X',
            'linkedin.com' => 'LinkedIn',
            'tiktok.com' => 'TikTok',
            'reddit.com' => 'Reddit',
            'snapchat.com' => 'Snapchat',
            'quora.com' => 'Quora',
            'tumblr.com' => 'tumblr',
            'vk.com' => 'VKontakte',
            'vkontakte.ru' => 'Vkontakte',
            'yandex.ru' => 'Yandex',
            'weibo.com' => 'Weibo',
            'whatsapp.com' => 'WhatsApp',
            'telegram.org' => 'Telegram',
            'medium.com' => 'Medium',
            'dailymotion.com' => 'Dailymotion',
            'twitch.tv' => 'Twitch',
            'wechat.com' => 'Wechat',
            'baidu.com' => 'Baidu',
            'line.me' => 'Line',
            'vimeo.com' => 'Vimeo',
            'meetup.com' => 'Meetup',
            'discord.com' => 'Discord',
            'duckduckgo.com' => 'DuckDuckGo',
            'ask.com' => 'Ask',
            'ecosia.org' => 'Ecosia',
            'qwant.com' => 'Qwant',
            'startpage.com' => 'Startpage',
            'pinterest.com' => 'Pinterest',
        ]);
    }

    public function getTopReferer(string $referer): string
    {
        $normalized = str($referer)
            ->lower()
            ->trim()
            ->replaceMatches('#^https?://#', '')
            ->replaceMatches('#^www\.#', '')
            ->before('/')
            ->toString();

        $key = $this->topReferers->search(function ($topRefererName, $topReferer) use ($normalized) {
            // google.co.uk, google.ch, google.de all become google.com
            $topRefererMain = str($topReferer)->beforeLast('.').'.';

            if (str($normalized)->startsWith($topRefererMain)) {
                return true;
            }

            // m.facebook.com, new.reddit.com, r.search.yahoo.com fold to canonical
            if (in_array($topReferer, $this->foldSubdomainsFor, true)
                && str($normalized)->endsWith('.'.$topReferer)) {
                return true;
            }

            return false;
        });

        $canonical = $key !== false ? $key : $normalized;

        return $this->aliases[$canonical] ?? $canonical;
    }

    public function getTopReferers(): Collection
    {
        return $this->topReferers;
    }
}
