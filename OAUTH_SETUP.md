# Google + Discord OAuth Setup (Local)

This project uses:
- `knpuniversity/oauth2-client-bundle`
- `league/oauth2-google`
- `wohali/oauth2-discord-new`

## 1) Add local secrets

Edit `.env.local` and add:

```env
OAUTH_GOOGLE_CLIENT_ID=your_google_client_id
OAUTH_GOOGLE_CLIENT_SECRET=your_google_client_secret
OAUTH_DISCORD_CLIENT_ID=your_discord_client_id
OAUTH_DISCORD_CLIENT_SECRET=your_discord_client_secret
```

Do not put real secrets in `.env` (committed file).

## 2) Provider callback URLs

Configure these exact redirect/callback URIs in the provider dashboards:

- Google: `http://localhost:8000/connect/google/check`
- Discord: `http://localhost:8000/connect/discord/check`

## 3) Google console requirements

- Authorized redirect URI:
  - `http://localhost:8000/connect/google/check`
- OAuth scopes used by app:
  - `email`
  - `profile`

## 4) Discord developer portal requirements

- Redirect:
  - `http://localhost:8000/connect/discord/check`
- OAuth scopes used by app:
  - `identify`
  - `email`

## 5) Apply config changes

Run:

```bash
php bin/console cache:clear
```

If your Symfony server is already running, restart it.

## 6) Quick verification

Routes should exist:

```bash
php bin/console debug:router | rg "connect_google|connect_discord"
```

Expected:
- `/connect/google`
- `/connect/google/check`
- `/connect/discord`
- `/connect/discord/check`

## 7) Common errors

- `Google login is not configured yet.` or `Discord login is not configured yet.`:
  - Missing values in `.env.local`.
- Provider says `redirect_uri_mismatch`:
  - Redirect URI in dashboard does not exactly match URL above.
- Provider says `invalid_client`:
  - Wrong client ID/secret pair or wrong app type in provider console.
