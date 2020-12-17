# OpenEuropa Media module

The OpenEuropa project uses media entities as a wrapper to any kind of assets such as images, documents, videos, etc. Therefore all asset field types should reference
media entities rather than directly files.

The OpenEuropa Media module provides various functionality that allows using Media on your site.

The following types of Media (bundles) are currently available:

* Images (local)
* Documents (local)
* Remote videos (supports Youtube, Vimeo, Daily Motion)
* AV Portal videos and photos
* Video iframes
* Iframes

Additionally, there is a demo module inside that exposes a content type and a generic Entity Browser meant to demonstrate the usage of Media with content.

## Permissions

The component provides a new permission called `view any unpublished media` which is used when checking access to viewing unpublished media entities.

**Table of contents:**

- [Known issues](#known-issues)
- [Development setup](#development-setup)
- [Contributing](#contributing)
- [Versioning](#versioning)

## Document media bundle

Out of the box, the Document media bundle provides the capability to upload a local document or choose a remote URL for the document, whose metadata will be pulled from that remote file (such as file type and size).

If you are updating from a version before `1.10.0` and you would like to benefit from the remote document capability, all you have to do is:

* Run the database updates as you normally would
* Edit the Document media form display and place the `File Type` and `Remote File` fields below the `Name` field. The most common order would be:
  * Name
  * File Type
  * File
  * Remote File

This will ensure the functionality kicks in.

## Known Issues

* The Daily Motion video URLs in the Remote video bundles need to have the HTTP scheme (not HTTPs).
* The Daily Motion thumbnail URLs are typically without an extension so the local copy is not usable. This is fixed in [#3080666](https://www.drupal.org/project/drupal/issues/3080666) so if
your version of Drupal core does not include that commit yet, you can apply the latest patch there.

## Development setup

You can build the test site by running the following steps.

* Install all the composer dependencies:

```bash
composer install
```

* Customize build settings by copying `runner.yml.dist` to `runner.yml` and
changing relevant values, like your database credentials.

* Install test site by running:

```bash
./vendor/bin/run drupal:site-install
```

Your test site will be available at `./build`.

### Using Docker Compose

Alternatively, you can build a development site using [Docker](https://www.docker.com/get-docker) and
[Docker Compose](https://docs.docker.com/compose/) with the provided configuration.

Docker provides the necessary services and tools such as a web server and a database server to get the site running,
regardless of your local host configuration.

#### Requirements:

- [Docker](https://www.docker.com/get-docker)
- [Docker Compose](https://docs.docker.com/compose/)

#### Configuration

By default, Docker Compose reads two files, a `docker-compose.yml` and an optional `docker-compose.override.yml` file.
By convention, the `docker-compose.yml` contains your base configuration and it's provided by default.
The override file, as its name implies, can contain configuration overrides for existing services or entirely new
services.
If a service is defined in both files, Docker Compose merges the configurations.

Find more information on Docker Compose extension mechanism on [the official Docker Compose documentation](https://docs.docker.com/compose/extends/).

#### Usage

To start, run:

```bash
docker-compose up
```

It's advised to not daemonize `docker-compose` so you can turn it off (`CTRL+C`) quickly when you're done working.
However, if you'd like to daemonize it, you have to add the flag `-d`:

```bash
docker-compose up -d
```

Then:

```bash
docker-compose exec web composer install
docker-compose exec web ./vendor/bin/run drupal:site-install
```

Using default configuration, the development site files should be available in the `build` directory and the development
site should be available at: [http://127.0.0.1:8080/build](http://127.0.0.1:8080/build).

#### Running the tests

To run the grumphp checks:

```bash
docker-compose exec web ./vendor/bin/grumphp run
```

To run the phpunit tests:

```bash
docker-compose exec web ./vendor/bin/phpunit
```

To run the behat tests:

```bash
docker-compose exec web ./vendor/bin/behat
```

## Contributing

Please read [the full documentation](https://github.com/openeuropa/openeuropa) for details on our code of conduct,
and the process for submitting pull requests to us.

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the available versions,
see the [tags on this repository](https://github.com/openeuropa/oe_media/tags).
