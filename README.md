<p align="center"><img src="public/img/laravel-react.png" width="400" alt="Laravel and React" /></p>

## Social Network

<p>A social network API is created in Laravel that allows developers to get the basics of social network and then customize it as per they want. It also benefits if you are frontend developer, you do not have to worry about learning and creating all the backend for a social network.</p>

### Features

1. User authentication (Register, Login, Logout)
2. Change password
3. Forgot password
4. User profile
5. Admin panel
6. Dynamic SMTP settings
7. Chat between user and admin (with attachments)
8. Create posts
9. Create friends and have chat with them
10. Like, comment and share post

### Tech stack

- PHP +8.2
- Laravel +10
- React +18
- Bootstrap +5

### How to setup

1. Goto file "config/database.php" and set your database credentials.

```
'mysql' => [
    ...

    'host' => '127.0.0.1',
    'port' => '3306',
    'database' => 'social_network',
    'username' => 'root',
    'password' => '',

    ...
],
```

Create a database named "social_network" in your phpMyAdmin.

2. Rename the file ".env.example" to just ".env"

3. At root folder, run the following commands:

(You can write any name, email or password of your choice for super admin while running 5th command)

```
1) COMPOSER_MEMORY_LIMIT=-1 composer update
2) php artisan key:generate
3) php artisan storage:link
4) php artisan migrate
5) name="Admin" email="admin@adnan-tech.com" password="admin" php artisan db:seed --class=DatabaseSeeder
6) php artisan serve
```

You can access the project from:
http://localhost:8000

For deployment, check our <a href="https://www.youtube.com/watch?v=EKJnV_-ZX0o" target="_blank">tutorial</a>.

If you face any issue in this, kindly let me know: support@adnan-tech.com