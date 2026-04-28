<?php

use App\Services\RefererService;

beforeEach(function () {
    $this->service = new RefererService;
});

test('strips https protocol and matches top referer', function () {
    expect($this->service->getTopReferer('https://reddit.com'))->toBe('reddit.com');
});

test('strips http protocol and matches top referer', function () {
    expect($this->service->getTopReferer('http://reddit.com'))->toBe('reddit.com');
});

test('strips protocol and leading www and trailing slash', function () {
    expect($this->service->getTopReferer('https://www.reddit.com/'))->toBe('reddit.com');
});

test('lowercases mixed case input', function () {
    expect($this->service->getTopReferer('HTTP://Reddit.COM'))->toBe('reddit.com');
});

test('maps regional google domains to google.com', function () {
    expect($this->service->getTopReferer('https://google.co.uk'))->toBe('google.com')
        ->and($this->service->getTopReferer('google.de'))->toBe('google.com')
        ->and($this->service->getTopReferer('https://www.google.ch/'))->toBe('google.com');
});

test('returns normalized fallback for unknown domains', function () {
    expect($this->service->getTopReferer('https://example.com'))->toBe('example.com')
        ->and($this->service->getTopReferer('https://www.example.com/'))->toBe('example.com');
});

test('preserves path-bearing referers as the mapped key', function () {
    expect($this->service->getTopReferer('https://reddit.com/r/php'))->toBe('reddit.com');
});

test('handles plain domain without protocol unchanged', function () {
    expect($this->service->getTopReferer('reddit.com'))->toBe('reddit.com');
});

test('folds reddit subdomains to reddit.com', function () {
    expect($this->service->getTopReferer('new.reddit.com'))->toBe('reddit.com')
        ->and($this->service->getTopReferer('old.reddit.com'))->toBe('reddit.com')
        ->and($this->service->getTopReferer('out.reddit.com'))->toBe('reddit.com')
        ->and($this->service->getTopReferer('en.reddit.com'))->toBe('reddit.com');
});

test('folds facebook mobile and link shim subdomains', function () {
    expect($this->service->getTopReferer('m.facebook.com'))->toBe('facebook.com')
        ->and($this->service->getTopReferer('l.facebook.com'))->toBe('facebook.com')
        ->and($this->service->getTopReferer('lm.facebook.com'))->toBe('facebook.com')
        ->and($this->service->getTopReferer('adsmanager.facebook.com'))->toBe('facebook.com');
});

test('folds instagram and youtube and baidu subdomains', function () {
    expect($this->service->getTopReferer('l.instagram.com'))->toBe('instagram.com')
        ->and($this->service->getTopReferer('m.youtube.com'))->toBe('youtube.com')
        ->and($this->service->getTopReferer('m.baidu.com'))->toBe('baidu.com');
});

test('maps yahoo TLD variants to yahoo.com', function () {
    expect($this->service->getTopReferer('yahoo.com'))->toBe('yahoo.com')
        ->and($this->service->getTopReferer('yahoo.de'))->toBe('yahoo.com')
        ->and($this->service->getTopReferer('yahoo.nl'))->toBe('yahoo.com')
        ->and($this->service->getTopReferer('yahoo.io'))->toBe('yahoo.com')
        ->and($this->service->getTopReferer('yahoo.work'))->toBe('yahoo.com');
});

test('keeps yahoo portal and yahoo search separate', function () {
    expect($this->service->getTopReferer('yahoo.com'))->toBe('yahoo.com')
        ->and($this->service->getTopReferer('search.yahoo.com'))->toBe('search.yahoo.com')
        ->and($this->service->getTopReferer('de.search.yahoo.com'))->toBe('search.yahoo.com');
});

test('folds regional yahoo search subdomains', function () {
    expect($this->service->getTopReferer('de.search.yahoo.com'))->toBe('search.yahoo.com')
        ->and($this->service->getTopReferer('uk.search.yahoo.com'))->toBe('search.yahoo.com')
        ->and($this->service->getTopReferer('r.search.yahoo.com'))->toBe('search.yahoo.com')
        ->and($this->service->getTopReferer('emea.search.yahoo.com'))->toBe('search.yahoo.com');
});

test('does not fold google application subdomains', function () {
    expect($this->service->getTopReferer('mail.google.com'))->toBe('mail.google.com')
        ->and($this->service->getTopReferer('drive.google.com'))->toBe('drive.google.com')
        ->and($this->service->getTopReferer('docs.google.com'))->toBe('docs.google.com')
        ->and($this->service->getTopReferer('accounts.google.com'))->toBe('accounts.google.com')
        ->and($this->service->getTopReferer('scholar.google.com'))->toBe('scholar.google.com')
        ->and($this->service->getTopReferer('gemini.google.com'))->toBe('gemini.google.com');
});

test('does not fold bing subdomains', function () {
    expect($this->service->getTopReferer('cn.bing.com'))->toBe('cn.bing.com')
        ->and($this->service->getTopReferer('edgeservices.bing.com'))->toBe('edgeservices.bing.com');
});

test('folds explicitly listed indeed and trustpilot subdomains', function () {
    expect($this->service->getTopReferer('nl.indeed.com'))->toBe('indeed.com')
        ->and($this->service->getTopReferer('de.indeed.com'))->toBe('indeed.com')
        ->and($this->service->getTopReferer('nl.trustpilot.com'))->toBe('trustpilot.com');
});

test('keeps wikipedia language subdomains separate', function () {
    expect($this->service->getTopReferer('en.wikipedia.org'))->toBe('en.wikipedia.org')
        ->and($this->service->getTopReferer('it.wikipedia.org'))->toBe('it.wikipedia.org')
        ->and($this->service->getTopReferer('de.wikipedia.org'))->toBe('de.wikipedia.org');
});

test('keeps domain subdomains where the prefix is meaningful', function () {
    // hn.algolia.com is Hacker News, not Honduras
    // my.kaspersky.com is the user account portal, not Malaysia
    expect($this->service->getTopReferer('hn.algolia.com'))->toBe('hn.algolia.com')
        ->and($this->service->getTopReferer('my.kaspersky.com'))->toBe('my.kaspersky.com')
        ->and($this->service->getTopReferer('eu.myconnectwise.net'))->toBe('eu.myconnectwise.net');
});

test('aliases youtu.be to youtube.com', function () {
    expect($this->service->getTopReferer('https://youtu.be/abcdef'))->toBe('youtube.com')
        ->and($this->service->getTopReferer('youtu.be'))->toBe('youtube.com');
});

test('aliases t.co to x.com', function () {
    expect($this->service->getTopReferer('t.co'))->toBe('x.com')
        ->and($this->service->getTopReferer('https://t.co/abc'))->toBe('x.com');
});

test('aliases twitter.com and its subdomains to x.com', function () {
    expect($this->service->getTopReferer('twitter.com'))->toBe('x.com')
        ->and($this->service->getTopReferer('https://twitter.com'))->toBe('x.com')
        ->and($this->service->getTopReferer('mobile.twitter.com'))->toBe('x.com')
        ->and($this->service->getTopReferer('m.twitter.com'))->toBe('x.com');
});

test('consolidates threads.net variants to threads.com', function () {
    expect($this->service->getTopReferer('threads.com'))->toBe('threads.com')
        ->and($this->service->getTopReferer('threads.net'))->toBe('threads.com')
        ->and($this->service->getTopReferer('l.threads.com'))->toBe('threads.com')
        ->and($this->service->getTopReferer('l.threads.net'))->toBe('threads.com');
});

test('aliases vkontakte.ru to vk.com and folds vk subdomains', function () {
    expect($this->service->getTopReferer('vkontakte.ru'))->toBe('vk.com')
        ->and($this->service->getTopReferer('vk.com'))->toBe('vk.com')
        ->and($this->service->getTopReferer('m.vk.com'))->toBe('vk.com');
});

test('aliases google android app packages', function () {
    expect($this->service->getTopReferer('com.google.android.googlequicksearchbox'))->toBe('google.com')
        ->and($this->service->getTopReferer('com.google.android.youtube'))->toBe('youtube.com')
        ->and($this->service->getTopReferer('com.google.android.gm'))->toBe('mail.google.com')
        ->and($this->service->getTopReferer('com.google.android.apps.maps'))->toBe('maps.google.com');
});

test('aliases social android app packages', function () {
    expect($this->service->getTopReferer('com.facebook.katana'))->toBe('facebook.com')
        ->and($this->service->getTopReferer('com.twitter.android'))->toBe('x.com')
        ->and($this->service->getTopReferer('com.instagram.android'))->toBe('instagram.com')
        ->and($this->service->getTopReferer('com.instagram.barcelona'))->toBe('threads.com')
        ->and($this->service->getTopReferer('com.linkedin.android'))->toBe('linkedin.com')
        ->and($this->service->getTopReferer('com.snapchat.android'))->toBe('snapchat.com')
        ->and($this->service->getTopReferer('com.pinterest'))->toBe('pinterest.com')
        ->and($this->service->getTopReferer('com.whatsapp'))->toBe('whatsapp.com');
});

test('aliases reddit android clients including third party', function () {
    expect($this->service->getTopReferer('com.reddit.frontpage'))->toBe('reddit.com')
        ->and($this->service->getTopReferer('com.laurencedawson.reddit_sync'))->toBe('reddit.com')
        ->and($this->service->getTopReferer('com.laurencedawson.reddit_sync.pro'))->toBe('reddit.com')
        ->and($this->service->getTopReferer('io.syncapps.lemmy_sync'))->toBe('reddit.com');
});

test('aliases telegram messenger forks', function () {
    expect($this->service->getTopReferer('org.telegram.messenger'))->toBe('telegram.org')
        ->and($this->service->getTopReferer('org.telegram.messenger.web'))->toBe('telegram.org')
        ->and($this->service->getTopReferer('org.aka.messenger'))->toBe('telegram.org')
        ->and($this->service->getTopReferer('org.telegram.biftogram'))->toBe('telegram.org')
        ->and($this->service->getTopReferer('org.telegram.plus'))->toBe('telegram.org');
});

test('aliases email android app packages', function () {
    expect($this->service->getTopReferer('com.aol.mobile.aolapp'))->toBe('mail.aol.com')
        ->and($this->service->getTopReferer('com.mailchimp.mailchimp'))->toBe('mailchimp.com')
        ->and($this->service->getTopReferer('com.microsoft.office.outlook'))->toBe('outlook.com')
        ->and($this->service->getTopReferer('com.yahoo.mobile.client.android.mail'))->toBe('mail.yahoo.com');
});

test('aliases slack android app and web app', function () {
    expect($this->service->getTopReferer('com.slack'))->toBe('slack.com')
        ->and($this->service->getTopReferer('app.slack.com'))->toBe('slack.com');
});

test('aliases niche reading clients', function () {
    expect($this->service->getTopReferer('com.talklittle.android.tildes'))->toBe('tildes.net')
        ->and($this->service->getTopReferer('io.github.hidroh.materialistic'))->toBe('news.ycombinator.com');
});

test('does not alias unrelated com.x.y identifiers', function () {
    expect($this->service->getTopReferer('com.king.candycrushsaga'))->toBe('com.king.candycrushsaga')
        ->and($this->service->getTopReferer('com.king.candycrushsodasaga'))->toBe('com.king.candycrushsodasaga')
        ->and($this->service->getTopReferer('com.all-url.info'))->toBe('com.all-url.info')
        ->and($this->service->getTopReferer('org.joinmastodon.android'))->toBe('org.joinmastodon.android');
});

test('folds duckduckgo and search engine subdomains', function () {
    expect($this->service->getTopReferer('html.duckduckgo.com'))->toBe('duckduckgo.com')
        ->and($this->service->getTopReferer('noai.duckduckgo.com'))->toBe('duckduckgo.com')
        ->and($this->service->getTopReferer('start.duckduckgo.com'))->toBe('duckduckgo.com');
});

test('folds linkedin country subdomains', function () {
    expect($this->service->getTopReferer('nl.linkedin.com'))->toBe('linkedin.com');
});

test('aliases lnkd.in shortlinks to linkedin.com', function () {
    expect($this->service->getTopReferer('lnkd.in'))->toBe('linkedin.com')
        ->and($this->service->getTopReferer('https://lnkd.in/abc123'))->toBe('linkedin.com');
});
