# WP Dashboard Notes

Sticky notes on the WordPress dashboard — shared team notes plus personal notes, colour-coded, with one-click delete.

## Why

Every team running a WordPress site has the same problem: reminders live in chat threads, email, or someone's head. "Don't update WooCommerce until Monday", "Client X's launch is Friday", "Remember the staging password changed". A notes widget on the dashboard puts them exactly where the team already works every day.

## Features

- **Team notes** — visible to everyone who can see the dashboard; anyone with `edit_posts` can add or delete them.
- **Personal notes** — visible only to you, stored in your user meta; any logged-in user can keep their own.
- **Colour-coded** — yellow, green, blue or pink, so urgent notes stand out.
- **Author + date** — every note shows who wrote it and when.
- **Capped storage** — 50 notes per list, 500 characters per note; newest first.
- **No JavaScript** — plain forms with nonces, so it works everywhere.

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate it from **Plugins**.
3. Open the **Dashboard** — the Notes widget is there. Tick "Shared with the team" for team notes, leave it off for personal ones.

## Uninstall

Deleting the plugin removes all shared notes and every user's personal notes.
