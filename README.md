# Slack-Toggl

[Hammock](http://github.com/tinyspeck/hammock) plugin for [Toggl](http://toggl.com) integration service in [Slack](http://slack.com).

## How to install

1. Clone into `hammock/plugins/toggl`.
2. "Add New Integration" in Hammock.
3. Add Outgoing WebHook integration in Slack. The `view.php` page specifies the URL to use.

## How to use

With trigger set to `toggl`:

- toggl create _duration_ _[description]_
- toggl start _[description]_
- toggl stop _id_
