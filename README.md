# Slim Framework 4 Skeleton Application

[![Deploy](https://www.herokucdn.com/deploy/button.svg)](https://heroku.com/deploy)
[![CircleCI](https://circleci.com/gh/hjemmel/todo-api-php.svg?style=svg)](https://circleci.com/gh/hjemmel/todo-api-php)
[![codecov](https://codecov.io/gh/hjemmel/todo-api-php/branch/master/graph/badge.svg)](https://codecov.io/gh/hjemmel/todo-api-php)
[![CodeFactor](https://www.codefactor.io/repository/github/hjemmel/todo-api-php/badge/master)](https://www.codefactor.io/repository/github/hjemmel/todo-api-php/overview/master)

Use this skeleton application to quickly setup and start working on a new Slim Framework 4 application. This application uses the latest Slim 4 with Slim PSR-7 implementation and PHP-DI container implementation. It also uses the Monolog logger.

This skeleton application was built for Composer. This makes setting up a new Slim Framework application quick and easy.

## Install the Application

Run this command from the directory in which you want to install your new Slim Framework application.

```bash
composer create-project slim/slim-skeleton [my-app-name]
```

Replace `[my-app-name]` with the desired directory name for your new application. You'll want to:

* Point your virtual host document root to your new application's `public/` directory.
* Ensure `logs/` is web writable.

To run the application in development, you can run these commands 

```bash
cd [my-app-name]
composer start
```

Run this command in the application directory to run the test suite

```bash
composer test
```

## Docker

Docker is the supported way to work on this project without installing PHP locally. The
image pins PHP 8.4 with Composer and Xdebug, matching what CI runs.

Start the dev server:

```bash
docker compose up slim -d
```

After that, open `http://localhost:8080` in your browser.

The `cli` service is a one-shot container for Composer and PHPUnit:

```bash
docker compose run --rm cli composer install
docker compose run --rm cli composer test
docker compose run --rm cli composer outdated --direct
```

To run the suite with coverage, as CI does:

```bash
docker compose run --rm cli composer test -- --coverage-text
```

Hitting the `/todos` endpoints needs real Firebase credentials: copy
`firebase-key-template.json` to `public/firebase-key.json` and fill in `.env.development`.
Without them the app still boots and serves `/`, but `/todos` returns a 500.

That's it! Now go build something cool.
