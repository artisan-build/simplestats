<?php

namespace App\Services;

use Illuminate\Support\Collection;

class RefererService
{
    private Collection $topReferers;

    public function __construct()
    {
        $this->topReferers = collect([
            // google with "www" because we want "mail.google.com" separate....
            'www.google.com' => 'Google',
            'facebook.com' => 'Facebook',
            'youtube.com' => 'YouTube',
            'instagram.com' => 'Instagram',
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
        ]);
    }

    public function getTopReferer(string $referer): string
    {
        return $this->topReferers->search(function ($topRefererName, $topReferer) use ($referer) {
            // google.co.uk, google.ch, google.de all become google.com
            $topRefererMain = str($topReferer)->beforeLast('.').'.';
            return str($referer)->contains($topRefererMain);
        }) ?: $referer;
    }

    public function getTopReferers(): Collection
    {
        return $this->topReferers;
    }
}
