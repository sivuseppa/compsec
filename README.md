# Compsec App

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
