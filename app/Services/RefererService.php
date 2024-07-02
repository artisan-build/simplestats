<?php

namespace App\Services;

use Illuminate\Support\Collection;

class RefererService
{
    private Collection $topReferers;

    public function __construct()
    {
        $this->topReferers = collect([
            "www.google.com",
            "facebook.com",
            "youtube.com",
            "instagram.com",
            "bing.com",
            "search.yahoo.com",
            "search.brave.com",
            "twitter.com",
            "linkedin.com",
            "tiktok.com",
            "pinterest.com",
            "reddit.com",
            "snapchat.com",
            "quora.com",
            "tumblr.com",
            "vkontakte.ru",
            "yandex.ru",
            "weibo.com",
            "whatsapp.com",
            "telegram.org",
            "medium.com",
            "flickr.com",
            "dailymotion.com",
            "twitch.tv",
            "wechat.com",
            "baidu.com",
            "line.me",
            "vimeo.com",
            "meetup.com",
            "discord.com",
            "duckduckgo.com",
            "ask.com",
            "ecosia.org",
            "qwant.com",
            "startpage.com",
            "myspace.com"
        ]);
    }

    public function getTopReferer(string $referer): string
    {
        return $this->topReferers->first(function ($topReferer) use ($referer) {
            // google.co.uk, google.ch, google.de all become google.com
            $topRefererMain = str($topReferer)->beforeLast('.').'.';
            return str($referer)->contains($topRefererMain);
        }) ?? $referer;
    }
}
