# Introduction

_**SimpleStats**_ is a streamlined statistics tool tailored for **Laravel** applications, transcending mere counts of views and visits. It offers **precise insights** into user origins and behaviors. With default tracking and filtering via [UTM](https://en.wikipedia.org/wiki/UTM_parameters) codes, you gain detailed analysis of **marketing** campaigns, identifying which efforts drive **revenue**. Effortlessly evaluate campaign **ROI**, discover cost-effective user acquisition channels, and pinpoint the most effective performance channels. _SimpleStats_ ensures full **GDPR compliance** and a minimalistic and straightforward installation process.

## How to set up SimpleStats in just 5 minutes

Are you a visual learner? Here you can learn how to set up SimpleStats in just a few minutes.

<video width="100%" controls poster="https://simplestats.io/images/screenshot.png">
  <source src="https://simplestats.io/storage/intro.mp4" type="video/mp4">
  Your browser does not support the video tag.
</video>

## How does it work?

### API Communication
Basically all we do is provide a [composer package](/installation), which allows you to simply define and set your [User](/how-to-track-a-new-user) and [Payment](how-to-track-a-new-payment) model and [Login](/how-to-track-a-new-login) event in the package config file. Then we listen if a new user or payment gets created or a login event occurs, and we send an [API call](/queue) to our service. 

### Data Visualisation

We collect this data in a GDPR-compliant way, aggregate it and make it filterable and visible for you on our nice looking dashboard at https://simplestats.io. This allows you to see how your campaigns and users perform during a selected time period filtered by UTMs and also how they perform over lifetime!

### Teams, Projects and Instances

You can invite co-workers to your **team** and assign roles, such as _Administrator_, _Editor_, _Viewer_ to them. Furthermore, if you like and want to, you can create multiple **projects** and multiple **instances** per project. For example, in a gaming app, you might have multiple game worlds per project like "Game World 1", "Game World 2", etc. This allows you to filter and manage them separately. If you do not plan to use multiple instances, you can just leave it as "Default". The structure and naming of your projects and instances are completely up to you!

## Server-Side vs Client-Side Tracking

**Ad blockers** and other privacy tools have a significant impact on the effectiveness of client-side analytics. Estimates suggest that up to **30-50%** of tracking data can be **blocked** when using client-side analytics due to these tools.
This can lead to incomplete datasets, skewed metrics, and ultimately, inaccurate insights.

On the other hand, server-side analytics are largely unaffected by ad blockers since the data collection occurs on the server rather than the user’s device.
This method ensures more accurate and comprehensive data collection, as it bypasses the limitations imposed by client-side tracking methods.

Furthermore, server-side tracking offers enhanced data security and privacy since it does not rely on third-party cookies and is less vulnerable to browser restrictions and privacy settings.
This makes it a more reliable choice for collecting accurate data and maintaining compliance with data protection regulations like **GDPR**.

In summary, while client-side analytics are easier to implement, server-side analytics provide more accurate and comprehensive data,
unaffected by ad blockers and privacy tools, making them a better choice for businesses needing reliable metrics.

## KPIs
 
On our dashboard, we provide you with the following [KPIs](https://en.wikipedia.org/wiki/Performance_indicator).

::: info
Don't worry if you do not understand the list below immediately.
It's easier to understand it when you see it in action on the dashboard. 
:::

### KPIs over time period

[//]: # (| **GROSS**  | Gross revenue                      | Gross revenue for the selected period.                                                                                                                      |)

| Key          | Short                              | Description                                                                                                                                                 |
|--------------|------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **NET**      | Net revenue                        | Net revenue for the selected period.                                                                                                                        |
| **FEE**      | Fee                                | Fee for the selected period.                                                                                                                                |
| **GROSS**    | Gross revenue                      | Gross revenue for the selected period.                                                                                                                      |
| **VISITORS** | Unique Visitors                    | Number of unique visitors for the selected period.                                                                                                          |
| **REG**      | Registrations                      | Number of new registrations for the selected period.                                                                                                        |
| **CR**       | Conversion Rate                    | Percentag of unique visitors who registered at the selected period.                                                                                         |
| **ARPDAU**   | Average revenue Per DAU            | The average revenue per DAU                                                                                                                                 |
| **NAU**      | New Active Users                   | Number of unique users who returned at the selected period to the app (=2nd login) after the registration (=1st login).                                     |
| **NAPU**     | New Active Paying Users            | Number of unique users who did their first payment at the selected period.                                                                                  |
| **DAU**      | Daily Active Users                 | Number of unique users who logged in at the selected period (without 1st login = registration).                                                             |
| **DAPU**     | Daily Active Paying Users          | Number of unique users who logged in or registered at the selected period and did at least one payment over lifetime (including the selected period)        |
| **DAAPU**    | Daily Active Active Paying Users   | Number of unique users who logged in or registered during the given period and did at least one payment in the last 20 days (including the selected period) |
| **WAU**      | Weekly Active Users                | Number of unique users who logged in at the selected period (without 1st login = registration).                                                             |
| **WAPU**     | Weekly Active Paying Users         | Number of unique users who logged in or registered at the selected period and did at least one payment over lifetime (including the selected period)        |
| **WAAPU**    | Weekly Active Active Paying Users  | Number of unique users who logged in or registered during the given period and did at least one payment in the last 20 days (including the selected period) |
| **MAU**      | Monthly Active Users               | Number of unique users who logged in at the selected period (without 1st login = registration).                                                             |
| **MAPU**     | Monthly Active Paying Users        | Number of unique users who logged in or registered at the selected period and did at least one payment over lifetime (including the selected period)        |
| **MAAPU**    | Monthly Active Active Paying Users | Number of unique users who logged in or registered during the given period and did at least one payment in the last 20 days (including the selected period) |

### KPIs over lifetime

| Key          | Short                           | Description                                                                |
|--------------|---------------------------------|----------------------------------------------------------------------------|
| **NET**      | Total Net revenue               | Net revenue over lifetime.                                                 |
| **FEE**      | Total Fee                       | Fee over lifetime.                                                         |
| **GROSS**    | Total Gross revenue             | Gross revenue over lifetime.                                               |
| **VISITORS** | Total Unique Visitors           | Number of unique visitors over lifetime.                                   |
| **REG**      | Total Registrations             | Number of registrations over lifetime.                                     |
| **CR**       | Total Conversion Rate           | Percentag of unique visitors who registered over lifetime.                                     |
| **ARPU**     | Average revenue Per User        | The average revenue per User (how much is a user/lead/reg worth in money). |
| **ARPPU**    | Average revenue Per Paying User | The average revenue per paying User.                                       |
| **PU**       | Paying Users                    | Number of paying users over lifetime.                                      |
| **REG2DAU**  | Reg. to DAU                     | Percentage of registered users who become DAUs                             |
| **REG2WAU**  | Reg. to WAU                     | Percentage of registered users who become WAUs                             |
| **REG2MAU**  | Reg. to MAU                     | Percentage of registered users who become MAUs                             |
| **REG2PU**   | Reg. to Pay                     | Percentage of registered users who become paying user.                     |
| **REG2TAU**  | Reg. to Active                  | Percentage of registered users who become active.                          |

## Help

Do you have a question? Feel free to reach out to us if you need some [help](https://tawk.to/chat/6601f04ea0c6737bd124880f/1hprp5d8g).
