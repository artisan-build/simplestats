# Introduction

_**SimpleStats**_ is a streamlined analytics tool tailored for **Laravel** applications, transcending mere counts of views and visits. It offers **precise insights** into user origins and behaviors. With default tracking and filtering via [UTM](https://en.wikipedia.org/wiki/UTM_parameters) codes, you gain detailed analysis of **marketing** campaigns, identifying which efforts drive **revenue**. Effortlessly evaluate campaign **ROI**, discover cost-effective user acquisition channels, and pinpoint the most effective performance channels. _SimpleStats_ ensures full **GDPR compliance** and a minimalistic and straightforward installation process.

## How to set up SimpleStats in just a few minutes

Are you a visual learner? Here you can learn how to set up SimpleStats in just a few minutes.

<video width="100%" controls poster="https://simplestats.io/images/screenshot.png">
  <source src="https://simplestats.io/storage/intro.mp4" type="video/mp4">
  Your browser does not support the video tag.
</video>

## How does it work?

### API Communication
Basically all we do is provide a [composer package](/installation), which allows you to simply define and set your [User](/how-to-track-a-new-user) and [Payment](how-to-track-a-new-payment) model and [Login](/how-to-track-a-new-login) event in the package config file. Then we listen if a new user or payment gets created or a login event occurs, and we send an [API call](/queue) to our service.

### Data Visualisation

We collect this data in a GDPR-compliant way, aggregate it and make it filterable and visible for you on our nice-looking dashboard at https://simplestats.io. This allows you to see how your campaigns and users perform during a selected time period filtered by UTMs, referrers, and countries!

### Teams and Projects

You can invite co-workers to your **team** and assign roles, such as _Administrator_, _Editor_, _Viewer_ to them. Each team can have multiple **projects**. You can view and analyze each project separately – or get an aggregated overview at the team level that combines the stats of all your projects.

## Server-Side vs Client-Side Tracking

**Ad blockers** and other privacy tools have a significant impact on the effectiveness of client-side analytics. Estimates suggest that up to **30-50%** of tracking data can be **blocked** when using client-side analytics due to these tools.
This can lead to incomplete datasets, skewed metrics, and ultimately, inaccurate insights.

On the other hand, server-side analytics like _SimpleStats_ are largely unaffected by ad blockers since the data collection occurs on the server rather than the user's device.
This method ensures more accurate and comprehensive data collection, as it bypasses the limitations imposed by client-side tracking methods.

Furthermore, server-side tracking offers enhanced data security and privacy since it does not rely on third-party cookies and is less vulnerable to browser restrictions and privacy settings.
This makes it a more reliable choice for collecting accurate data and maintaining compliance with data protection regulations like **GDPR**.

In summary, while client-side analytics are easier to implement, server-side analytics provide more accurate and comprehensive data,
unaffected by ad blockers and privacy tools, making them a better choice for businesses needing reliable metrics.

## KPIs

SimpleStats computes a wide range of [KPIs](https://en.wikipedia.org/wiki/Performance_indicator) from your tracking data, covering acquisition, revenue, retention, and recurring revenue. Which ones apply depends on what your project tracks: visitors only, registrations, payments, or full subscription analytics.

See the full [KPIs reference](/kpis) for every metric, what it means, and how it is calculated.

## Help

Do you have a question? Feel free to reach out to us if you need some [help](https://tawk.to/chat/6601f04ea0c6737bd124880f/1hprp5d8g) or check out our [troubleshooting](troubleshooting.md) page.
