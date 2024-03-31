# Track Users

Here you can set three different tracking types. The first is the login
event. If this event gets dispatched, we track a login. The second is
the user model. If such a model is created, we track a registration.

As the payment model is named very individually, we did not set any default here.
Give it the name of the model which holds your payments or transactions data.

## Track with condition

## Tracking Codes

Below you can set your tracking code URL param names. We already set some
classical defaults for you, but you're free to change them as you like.
Note that only the params which are listed here are getting tracked!


HTTP REFERER ist default, so even if someone links your site without a utm code,
we track the referrer for you.
