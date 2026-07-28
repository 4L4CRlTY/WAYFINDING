SMART CAMPUS WAYFINDING - HOSTINGER SHARED HOSTING FIX
======================================================

IMPORTANT SECURITY STEP
-----------------------
The previous database password and APP_KEY were exposed. Before continuing:

1. Open Hostinger hPanel.
2. Go to Websites > Dashboard > Databases Management.
3. Open the three-dot menu beside the database.
4. Choose Change Password and generate a new strong password.
5. Do not reuse or share the old password.


FILES IN THIS PACKAGE
---------------------

.env.hostinger.example
    Clean production environment template with one configuration block.

.htaccess
    Correct root rewrite file. It fixes the old "publicc/" typo and prevents
    repeated public/public rewrites that can cause a 403 response.

deploy-hostinger.sh
    Safe deployment helper for Hostinger SSH.


UPLOAD INSTRUCTIONS
-------------------

The Laravel project folder is the folder containing:

    artisan
    app/
    bootstrap/
    public/
    routes/
    storage/
    vendor/

1. Upload this package's .htaccess to that Laravel project folder and replace
   the old root .htaccess.

2. Open .env.hostinger.example and replace:

    https://YOUR-DOMAIN.com
    YOUR_HOSTINGER_DATABASE
    YOUR_HOSTINGER_DATABASE_USER
    YOUR_NEW_DATABASE_PASSWORD

3. Save the completed file as exactly:

    .env

   There must be only one APP_NAME block. Do not append it to the old .env.

4. Upload deploy-hostinger.sh to the same folder as artisan.

5. Confirm that the domain either:

   a. points directly to PROJECT_FOLDER/public, or
   b. points to the project folder where the supplied root .htaccess forwards
      requests into public/.

6. In hPanel, set the website PHP version to PHP 8.3 or newer.


RUN THROUGH SSH
---------------

Enable SSH in hPanel, connect, and enter the project folder. Example:

    cd ~/domains/YOUR-DOMAIN.com/public_html

Give the helper permission and run it:

    chmod 700 deploy-hostinger.sh
    ./deploy-hostinger.sh

If the project is inside a wayfinding folder, use:

    cd ~/domains/YOUR-DOMAIN.com/public_html/wayfinding


MANUAL COMMANDS (IF THE SCRIPT CANNOT RUN)
------------------------------------------

Run these from the folder containing artisan:

    composer install --no-dev --prefer-dist --optimize-autoloader
    php artisan optimize:clear
    php artisan key:generate --force
    php artisan migrate --force
    php artisan storage:link
    php artisan optimize

Only run key:generate once for a new production installation. Running it again
later will invalidate encrypted sessions and existing encrypted values.


FINAL CHECKS
------------

1. Open https://YOUR-DOMAIN.com/up
   Expected response: HTTP 200 and a healthy application.

2. Open https://YOUR-DOMAIN.com

3. Test login, admin pages, user map, GPS permission, route search, indoor
   navigation, uploaded images, and the PWA install prompt.

4. If an error remains, temporarily inspect:

    storage/logs/laravel.log

   Keep APP_DEBUG=false on the public website.
