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

- [Upgrade to 1.32.0](#upgrade-to-1320)
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

## Upgrade to `1.32.0`
This version requires the version of Media AV Portal `2.0^RC`, which no longer depend on remote_stream_wrapper module.

If you are upgrading using composer, you should composer require drupal/remote_stream_wrapper either before or after downloading the new oe_media version (`composer require drupal/remote_stream_wrapper:^2.1 -W`).
If remote_stream_wrapper is not required by other modules or not used directly by your site, after you finish with updating oe_media module, you can then uninstall the remote_stream_wrapper module and remove the dependency once and for all.

If you are still using manual downloads, just update oe_media module, uninstall remote_stream_wrapper and then feel free to delete it from your filesystem (only if not used by other modules).

## Development setup

You can build the test site by running the following steps.

* Install all the composer dependencies:

```bash
composer install
```

* Customize build settings by copying `runner.yml.dist` to `runner.yml` and
changing relevant values, like your database credentials.

This will also:
- Symlink the theme in  `./build/modules/custom/oe_media` so that it's available for the test site
- Setup Drush and Drupal's settings using values from `./runner.yml.dist`.
- Setup PHPUnit and Behat configuration files using values from `./runner.yml.dist`

**Please note:** project files and directories are symlinked within the test site by using the
[OpenEuropa Task Runner's Drupal project symlink](https://github.com/openeuropa/task-runner-drupal-project-symlink)
command.

If you add a new file or directory in the root of the project, you need to re-run `drupal:site-setup` in order to make
sure they are correctly symlinked.

If you don't want to re-run a full site setup for that, you can simply run:

```
$ ./vendor/bin/run drupal:symlink-project
```

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

#### Step debugging

To enable step debugging from the command line, pass the `XDEBUG_SESSION` environment variable with any value to
the container:

```bash
docker-compose exec -e XDEBUG_SESSION=1 web <your command>
```

Please note that, starting from XDebug 3, a connection error message will be outputted in the console if the variable is
set but your client is not listening for debugging connections. The error message will cause false negatives for PHPUnit
tests.

To initiate step debugging from the browser, set the correct cookie using a browser extension or a bookmarklet
like the ones generated at https://www.jetbrains.com/phpstorm/marklets/.

## Contributing

Please read [the full documentation](https://github.com/openeuropa/openeuropa) for details on our code of conduct,
and the process for submitting pull requests to us.

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the available versions,
see the [tags on this repository](https://github.com/openeuropa/oe_media/tags).
