Slack Rage
==========

Basic Silex app that handles a slash command for posting rage faces to slack.

## Setup

1. Clone the repo onto a server somewhere web accessible
2. Run `composer install`
3. Copy the sample config to config.php `cp config-sample.php config.php`
4. In slack integrations create a slash command called 'rage' for example
5. Set the slash command URL to point to your server where you cloned the repo
6. Copy the slash command token as a key in the `$webhooks` array in `config.php`
7. Create an incoming webhook in Slack, copy the URL as the value for the above array key
8. Commence raging
