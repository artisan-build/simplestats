# Data Import

Integrating _SimpleStats_ into an existing project doesn't mean leaving behind your historical data. Recognizing the value of your application's past insights, we've developed the Data Import feature. This functionality enables a smooth transition by allowing you to seamlessly **import** your **existing data** into _SimpleStats_, ensuring that you retain access to all historical metrics and insights.

::: info
The Data Import feature is not available for the free subscription plan.
:::

## Supported Formats

The Data Import supports the following file formats: _**xls, xlsx**_ and _**csv**_.

## Import Users

Though it is not mandatory, it's highly recommended to **first** import the **user data** and only then the corresponding payment data.

To start an import, you have to provide a supported file format containing the user data to import. Therefore, you may run the following SQL query on your users table and export the output.

The query may look like this:

```sql
SELECT id, created_at as `time` FROM users WHERE 1;
```

track fields nicht vergessen

::: warning
If your users have any **conditions** to count as "valid", for instance only if they verified their email address, make sure to provide only these data in the import file.
:::

EXAMPLE SQL QUERIES FÜR MIT CONDITION UND OHNE

### Simulate Natural Login Behavior (SNLB)

::: danger
Coming soon
:::

## Import Payments


::: warning
If your payment data has any conditions to count as "valid", for instance only if the payment reached a certain status like "success", make sure to provide only these data in the import file. Also make sure to send us the currency of your payments in cents.
:::
