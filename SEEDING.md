# Seeding the Database on Railway

Since your application is now deployed and running, you can populate the database with your seed data.

## Option 1: Using Railway CLI (Recommended)

If you have installed the Railway CLI as mentioned in the deployment guide:

1.  Open your terminal in the `exam_api` folder.
2.  Login if you haven't: `railway login`
3.  Link your project: `railway link` (select your project)
4.  Run the seeder:
    ```bash
    railway run php artisan db:seed --force
    ```

## Option 2: Using the "Start Command" (Temporary)

If you don't want to install the CLI, you can modify your generic Start Command in Railway or your `nixpacks.toml` temporarily.

1.  Open `nixpacks.toml` in your editor.
2.  Change the `[start]` command to include seeding:
    ```toml
    [start]
    cmd = "php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan migrate --force && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=$PORT"
    ```
3.  Commit and push:
    ```bash
    git add nixpacks.toml
    git commit -m "Run seeders on deploy"
    git push
    ```
4.  Wait for the deployment to finish.
5.  **IMPORTANT:** Once deployed and verified, **revert the change** in `nixpacks.toml` (remove `php artisan db:seed --force`) and push again. You don't want to re-seed your database on every single deployment!

## Verification

After seeding, check your application (e.g., try logging in with a seeded user) to confirm the data is there.
