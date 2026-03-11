# Home Sweet App

Project description goes here...

## Prerequisites

- PHP 8.4 or higher
- SQLite3 PHP extension
- Apache web server (database is currently protected with .htaccess)

## Installation

- Clone the repository: git clone https://github.com/sivuseppa/compsec.git
- Upload files to the public root folder of the server.
- Rename .env.example to .env and add environment variables
- After first login on a production app, remove admin password and environment type variables from .env

## Add extra protection for the database

- On a Linux web server, set file permissions: chmod 600 db.sqlite
- Move the database file outside the public root
