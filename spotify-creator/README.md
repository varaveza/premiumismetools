# PHP UI for Spotify Creator

This folder provides a minimal PHP front-end that:
- Enforces 1 submission per IP+UA per day (SQLite)
- Calls the Flask backend `/api/create` with a private header `X-API-Key`
- Never exposes passwords or backend keys to the public

## Configure

Set these environment variables for PHP (vhost/FPM pool or `.user.ini`):
- FLASK_API: URL to Flask endpoint (e.g. http://127.0.0.1:5111/api/create)
- FLASK_BACKEND_API_KEY: shared secret with Flask
- SPOTIFY_DOMAIN: default domain if the form leaves it blank
- SPOTIFY_PASSWORD: default password if the form leaves it blank

Flask must have `BACKEND_API_KEY` with the same value.

## Deploy
- Place this folder on your web root path (e.g. premiumisme.co/tools/spotify-creator)
- Ensure PHP can write to `spo_creator.db` in the project root
- Start Flask app separately; reverse proxy or local-only is OK

## Reset daily submissions
- Add a cron job to purge old rows:
  `sqlite3 spo_creator.db "DELETE FROM ip_submissions WHERE submitted_at < datetime('now','-2 days');"`


