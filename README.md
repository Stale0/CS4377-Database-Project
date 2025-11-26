Comprehensive Installation and run guide
prerequisites
    Download node.js - http://nodejs.org/en/download
    download laravel - https://laravel.com/docs/12.x - I personally recommend doing it through command line
    download xampp - https://www.apachefriends.org/download.html
    Download Repo/zip (includes database_project.sql file)

1. Xampp set up
   open xampp and start both apache and mysql modules (under actions).
   click into the admin action for mysql, this will open up a web browser for phpmyadmin
   create a new database named databaseproject
   click into it on the left side of the screen then look for and click into the import tab near the top.
   click choose file and look for database_project.sql, after inputting it scroll to the bottom and hit import
   you now have the sql database loaded within mysql

2. laraval set up
   Go into the project folder and look for .env.example.
   Rename this file to .env and save
   open up command prompt and cd into the repo folder
   NOTE- Ensure both apache and mysql modules are active in xampp or else you will get errors starting the server/connectiong to sql database
   run the following commands
       composer install
       npm install && npm run build
       php artisan key:generate
       php artisan migrate ---- Should be optional
       php artisan serve
   After you run php artisan serve open up chrome/explorer and go to localhost:8000 and you should see our web page

   When testing make sure to save workfile and then simply refresh browser.
   
