# php-docker-project

Slim framework based example web app and CLI scripts with Dockerized build running nginx-unit.
Basic scss to css compilation is also offered.

## Usage

See the Makefile for possible commands. Here's a quick summary:

* `make client-setup`: Runs npm install to set up SCSS and assets support
* `make watch`: Run the npm command to watch for scss changes and compile to css
* `make docker-up`: Spin up the docker-compose services
* `make server-dev`: start a local dev server using docker
* `make fix-all`: Format all PHP code and run static analysis

## CLI Scripts

Run `php ci-cd/runner.php` to see what commands are available.

Example run:

`php ci-cd/runner.php hello:world`: Print a hello world.

## Code style

Auto format the code using:

```
make fix-all
```

All directories and namespaces should be in **lowercase**. This makes it easier to differentiate folders/namespaces from actual concrete Class names.

## Docker

Upstream image is found at https://hub.docker.com/_/unit
