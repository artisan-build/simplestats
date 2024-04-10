# Introduction

_**SimpleStats**_ is a streamlined statistics tool tailored for **Laravel** applications, transcending mere counts of visits, views, and page impressions. It offers **precise insights** into user origins and behaviors. With default tracking and filtering via [UTM](https://en.wikipedia.org/wiki/UTM_parameters) codes, you gain detailed analysis of **marketing** campaigns, identifying which efforts drive **revenue**. Effortlessly evaluate campaign **ROI**, discover cost-effective user acquisition channels, and pinpoint the most effective performance channels. _SimpleStats_ ensures full **GDPR compliance** and a minimalistic and straightforward installation process.

![Dashboard](/screenshot.png)

## How does it work?

### API Communication
Basically all we do is provide a [composer package](/installation), which allows you to simply define and set your [User](/how-to-track-a-new-user) and [Payment](how-to-track-a-new-payment) model and [Login](/how-to-track-a-new-login) event in the package config file. Then we listen if a new user or payment gets created or a login event occurs, and we send an [API call](/queue) to our service. 

### Data Visualisation

We collect this data in a GDPR-compliant way, aggregate it and make it filterable and visible for you on our nice looking dashboard at https://simplestats.io. This allows you to see how your campaigns and users perform during a selected time period filtered by UTMs and also how they perform over lifetime!

### Teams, Projects and Instances

You can invite co-workers to your **team** and assign roles, such as _Administrator_, _Editor_, _Viewer_ to them. Furthermore, if you like and want to, you can create multiple **projects** and multiple **instances** per project, which allows you to structure and organize your App tracking into smaller "junks". For instance, if you have an app/project called _My great Shop_ you may want to "divide" it by countries. So you could create multiple instances for this project called: _Germany, Spain and Italy_. You may even want to have multiple instances per country like _DE1, DE2, DE3, ES1, ES2 and IT1_. The structure and naming of your projects and instances are completely up to you!

## KPIs
 
On our dashboard, we provide you with the following KPIs.

### KPIs over time period

| Key        | Short                              | Description                                                                                                                                                         |
|------------|------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **NET**    | Net revenue                        | Net revenue for the selected period.                                                                                                                                |
| **GROSS**  | Gross revenue                      | Gross revenue for the selected period.                                                                                                                              |
| **ARPDAU** | Average revenue Per DAU            | The average revenue per DAU                                                                                                                                         |
| **REG**    | Registrations                      | Number of new registrations for the selected period.                                                                                                                |
| **NAU**    | New Active Users                   | Number of unique users who returned at the selected period to the app (=2nd login) after the registration (=1st login).                                             |
| **NAPU**   | New Active Paying Users            | Number of unique users who did their first payment at the selected period.                                                                                          |
| **DAU**    | Daily Active Users                 | Number of unique users who logged in at the selected period (without 1st login = registration).                                                                     |
| **DAPU**   | Daily Active Paying Users          | Number of unique users who logged in or registered at the selected period and did at least one payment over lifetime (including the selected period)                |
| **DAAPU**  | Daily Active Active Paying Users   | Number of unique users who logged in or registered during the given '+periodName+' and did at least one payment in the last 20 days (including the selected period) |
| **WAU**    | Weekly Active Users                | Number of unique users who logged in at the selected period (without 1st login = registration).                                                                     |
| **WAPU**   | Weekly Active Paying Users         | Number of unique users who logged in or registered at the selected period and did at least one payment over lifetime (including the selected period)                |
| **WAAPU**  | Weekly Active Active Paying Users  | Number of unique users who logged in or registered during the given '+periodName+' and did at least one payment in the last 20 days (including the selected period) |
| **MAU**    | Monthly Active Users               | Number of unique users who logged in at the selected period (without 1st login = registration).                                                                     |
| **MAPU**   | Monthly Active Paying Users        | Number of unique users who logged in or registered at the selected period and did at least one payment over lifetime (including the selected period)                |
| **MAAPU**  | Monthly Active Active Paying Users | Number of unique users who logged in or registered during the given '+periodName+' and did at least one payment in the last 20 days (including the selected period) |

### KPIs over lifetime

| Key         | Short                           | Description                                                                |
|-------------|---------------------------------|----------------------------------------------------------------------------|
| **NET**     | Net revenue                     | Net revenue over lifetime.                                                 |
| **ARPU**    | Average revenue Per User        | The average revenue per User (how much is a user/lead/reg worth in money). |
| **ARPPU**   | Average revenue Per Paying User | The average revenue per paying User.                                       |
| **REG**     | Total Registrations             | Number of registrations over lifetime.                                     |
| **PU**      | Paying Users                    | Number of paying users over lifetime.                                      |
| **TAU**     | Total Active Users              | Number of users who logged in at least twice over lifetime.                |
| **REG2DAU** | Reg. to DAU                     | Percentage of registered users who become DAUs                             |
| **REG2WAU** | Reg. to WAU                     | Percentage of registered users who become WAUs                             |
| **REG2MAU** | Reg. to MAU                     | Percentage of registered users who become MAUs                             |
| **REG2PU**  | Reg. to Pay                     | Percentage of registered users who become paying user.                     |
| **REG2TAU** | Reg. to Active                  | Percentage of registered users who become active.                          |

