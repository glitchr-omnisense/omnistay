# The Docker harness

```sh
cd docker
echo 'OMNISTAY_PLUGINS=../..' > .env      # the providers' checkouts beside the core
docker compose run --rm omnistay test     # every package's tests, no network
docker compose run --rm omnistay bare     # plain PHP: hotels and homes searched, a stay booked, refused twice, cancelled
docker compose run --rm omnistay phpstan  # PHPStan at level max, each package
docker compose down -v
```

`docker/harness/setup.php` writes the harness's `composer.json` and `phpunit.xml` in the volume:
the core from its checkout, each provider of `docker/harness/plugins.php` (amadeus, local) from a
checkout under `OMNISTAY_PLUGINS` or else from GitHub (branch 1.x). The image is PHP 8.4 (Alpine)
with Composer; `compose.yml` names the entrypoint, so the image of another family's harness, which
is the same, can be tagged `omnisense/omnistay-harness` instead of building.

```
$ docker compose run --rm omnistay test
OK (56 tests, 303 assertions)
```

(2026-11-09, the core, amadeus and local from their checkouts; `phpstan` the same day: no error in
the three packages.)
