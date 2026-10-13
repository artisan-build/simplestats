<?php

namespace App\Services;

use Illuminate\Support\Collection;

class RefererService
{
    private readonly Collection $topReferers;

    /**
     * Top referers whose subdomains should fold back to the canonical domain.
     * Covers both named subdomains (m.facebook.com, l.facebook.com) and country
     * variants (nl.indeed.com, at.search.yahoo.com). Add a domain here when its
     * subdomains carry no meaningful distinction for our analytics.
     *
     * Explicitly NOT included: google.com, bing.com, wikipedia.org, kaspersky.com,
     * algolia.com (subdomains carry meaning, e.g. mail.google.com vs drive.google.com,
     * en.wikipedia.org vs de.wikipedia.org as different content corpora,
     * hn.algolia.com is Hacker News not Honduras-Algolia).
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
        'indeed.com',
        'trustpilot.com',
    ];

    /**
     * Hard aliases applied after the main lookup. Handles short links,
     * legacy domains, threads.net consolidation, and Android app package
     * identifiers (the bulk of the package list is sourced from
     * snowplow-referer-parser/referers.yml).
     *
     * @var array<string, string>
     */
    private array $aliases = [
        // Short links and legacy domains
        'youtu.be' => 'youtube.com',
        't.co' => 'x.com',
        'twitter.com' => 'x.com',
        'lnkd.in' => 'linkedin.com',
        'threads.net' => 'threads.com',
        'l.threads.net' => 'threads.com',
        'vkontakte.ru' => 'vk.com',

        // Slack web app
        'app.slack.com' => 'slack.com',

        // Android: Google apps
        'com.google.android.googlequicksearchbox' => 'google.com',
        'com.google.android.youtube' => 'youtube.com',
        'com.google.android.gm' => 'mail.google.com',
        'com.google.android.apps.maps' => 'maps.google.com',

        // Android: Social
        'com.facebook.katana' => 'facebook.com',
        'com.twitter.android' => 'x.com',
        'com.instagram.android' => 'instagram.com',
        'com.instagram.barcelona' => 'threads.com',
        'com.linkedin.android' => 'linkedin.com',
        'com.snapchat.android' => 'snapchat.com',
        'com.pinterest' => 'pinterest.com',
        'com.whatsapp' => 'whatsapp.com',
        'com.reddit.frontpage' => 'reddit.com',
        'com.laurencedawson.reddit_sync' => 'reddit.com',
        'com.laurencedawson.reddit_sync.pro' => 'reddit.com',
        'io.syncapps.lemmy_sync' => 'reddit.com',
        'com.talklittle.android.tildes' => 'tildes.net',
        'io.github.hidroh.materialistic' => 'news.ycombinator.com',

        // Android: Messaging (Telegram clients and forks)
        'org.telegram.messenger' => 'telegram.org',
        'org.telegram.messenger.web' => 'telegram.org',
        'org.aka.messenger' => 'telegram.org',
        'org.telegram.biftogram' => 'telegram.org',
        'org.telegram.plus' => 'telegram.org',

        // Android: Email
        'com.aol.mobile.aolapp' => 'mail.aol.com',
        'com.mailchimp.mailchimp' => 'mailchimp.com',
        'com.microsoft.office.outlook' => 'outlook.com',
        'com.yahoo.mobile.client.android.mail' => 'mail.yahoo.com',

        // Android: Slack
        'com.slack' => 'slack.com',
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
            'yahoo.com' => 'Yahoo',
            'search.yahoo.com' => 'Yahoo Search',
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
            'indeed.com' => 'Indeed',
            'trustpilot.com' => 'Trustpilot',
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

        $key = $this->topReferers->search(function ($topRefererName, $topReferer) use ($normalized): bool {
            // google.co.uk, google.ch, google.de all become google.com
            $topRefererMain = str($topReferer)->beforeLast('.').'.';

            if (str($normalized)->startsWith($topRefererMain)) {
                return true;
            }

            // m.facebook.com, new.reddit.com, r.search.yahoo.com fold to canonical
            return in_array($topReferer, $this->foldSubdomainsFor, true)
                && str($normalized)->endsWith('.'.$topReferer);
        });

        $canonical = $key !== false ? $key : $normalized;

        return $this->aliases[$canonical] ?? $canonical;
    }

    public function getTopReferers(): Collection
    {
        return $this->topReferers;
    }
}
