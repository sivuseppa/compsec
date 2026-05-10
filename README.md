# CompSec App

## Overview

This project is part of my exercise work for Secure Programming course in Tampere Univercity. It helps me understand...

## Prerequisites

- PHP 8.4 or higher
- SQLite3 PHP extension
- A web server (e.g., Apache, Nginx, or PHP built in web server)

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/sivuseppa/compsec.git
   ```
2. Upload files to the server and point the server document root to the /public/ folder of the application.
3. Rename .env.example to .env and change necessary environment variable values.
4. After first login on a production app, remove admin credentials and development environment type variable (APP_ENV=dev) from .env

## Tests

I assume that you have Composer (https://getcomposer.org/) installed in your system.

1. Install all dependencies including PHPUnit test suite with composer:

   ```php
   composer.phar update
   ```

2. Run tests:

   ```bash
   ./vendor/bin/phpunit tests
   ```

```
compsec
├─ data
│  ├─ db
│  └─ logs
├─ public
│  ├─ assets
│  │  └─ fonts
│  ├─ backend
│  │  └─ index.php
│  ├─ components
│  │  ├─ addTask.js
│  │  ├─ addUser.js
│  │  ├─ app.js
│  │  ├─ avatar.js
│  │  ├─ login.js
│  │  ├─ navigation.js
│  │  ├─ notice.js
│  │  ├─ setting.js
│  │  ├─ settings.js
│  │  ├─ store.js
│  │  ├─ task.js
│  │  ├─ tasks.js
│  │  ├─ user.js
│  │  └─ users.js
│  ├─ favicon.ico
│  ├─ index.html
│  └─ style.css
├─ src
│  ├─ app.php
│  ├─ auth.php
│  ├─ dotenv
│  │  └─ dotenv.php
│  ├─ functions.php
│  ├─ logger.php
│  ├─ mailer.php
│  ├─ phpmailer
|  │  └─ https://github.com/phpmailer/phpmailer
│  ├─ settings.php
│  ├─ task.php
│  └─ user.php
└─ tests
   └─ UserTest.php

```