# Deploying Laravel to Railway

Follow these steps to deploy your `exam_api` backend and a PostgreSQL database to Railway.

## Prerequisites

- You have a Railway account.
- Your code is pushed to a GitHub repository.

## Step 1: Create a New Project on Railway

1.  Log in to [Railway](https://railway.app/).
2.  Click **"New Project"**.
3.  Select **"Deploy from GitHub repo"**.
4.  Select your repository containing the `exam_api` code.
5.  Click **"Add Variables"** (don't deploy just yet).

## Step 2: Add Database (PostgreSQL)

1.  In your project view, right-click on the canvas (or click "New").
2.  Select **"Database"** -> **"PostgreSQL"**.
3.  Wait for the database service to be created.

## Step 3: Configure Environment Variables

Click on your **Laravel App Service** card, then go to the **Variables** tab. 

**CLICK "RAW EDITOR"** (to paste multiple variables at once) and paste the block below. Railway will automatically substitute the `${{...}}` variables with the actual values from your database service.

```env
APP_NAME="ExamAPI"
APP_ENV="production"
APP_KEY="base64:oEA4V0wIi0XKqTXfIijrw4NJMEoGkKmvsNcqIdsIAsc="
APP_DEBUG="false"
APP_URL="https://${{RAILWAY_PUBLIC_DOMAIN}}"
APP_LOCALE="en"
APP_FALLBACK_LOCALE="en"
APP_FAKER_LOCALE="en_US"

LOG_CHANNEL="stderr"
LOG_DEPRECATIONS_CHANNEL="null"
LOG_LEVEL="info"
LOG_STACK="single"

DB_CONNECTION="pgsql"
DB_HOST="${{PostgreSQL.DATABASE_HOST}}"
DB_PORT="${{PostgreSQL.DATABASE_PORT}}"
DB_DATABASE="${{PostgreSQL.DATABASE_NAME}}"
DB_USERNAME="${{PostgreSQL.DATABASE_USER}}"
DB_PASSWORD="${{PostgreSQL.DATABASE_PASSWORD}}"

BROADCAST_CONNECTION="log"
FILESYSTEM_DISK="local"
SESSION_DRIVER="file"
SESSION_LIFETIME="120"

MAIL_MAILER="log"
MAIL_HOST="127.0.0.1"
MAIL_PORT="2525"
MAIL_USERNAME="null"
MAIL_PASSWORD="null"
MAIL_ENCRYPTION="null"
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

NIXPACKS_CONFIG="nixpacks.toml"
```

*Note: `APP_KEY` provided here is the one you sent. You should ideally generate a fresh one for production.*

## Step 4: Deploy

1.  Once variables are set, a deployment might trigger automatically. If not, click **"Deploy"**.
2.  Watch the **Deployments** tab logs.
3.  Wait for the build to finish.

## Step 5: Run Migrations

To set up your database tables:
1.  Install Railway CLI locally: `npm i -g @railway/cli`
2.  Login: `railway login`
3.  Link your project: `railway link`
4.  Run migrations: `railway run php artisan migrate --force`
