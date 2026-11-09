# PeopleWeek Symfony 6.4 / 7.4 compatibility

The `sf7` branch starts at `sf6` commit `5f54cbafd9f0` and supports Symfony
`^6.4 || ^7.4`, with PHP 8.1 or newer (Symfony 7.4 requires PHP 8.2 or newer).
It can be installed while the application still runs Symfony 6.4. No version
checks or alternative production implementations are needed.

After pushing this branch, keep the PeopleWeek VCS repositories for both this
bundle and `oauth2-php`, and select:

```json
"friendsofsymfony/oauth-server-bundle": "dev-sf7"
```

Then update the bundle and review the lockfile:

```sh
composer update friendsofsymfony/oauth-server-bundle --with-dependencies
```

The root project must supply the `oauth2-php` repository: Composer does not inherit
repositories declared by dependencies. The existing PeopleWeek `oauth2-php`
`dev-master` supports both HttpFoundation release lines; no coordinated change to
that dependency is required.

Configure API firewalls with the Symfony authenticator system (the legacy
`fos_oauth: true` firewall option was already removed on `sf6`):

```yaml
security:
    firewalls:
        api:
            pattern: ^/api
            provider: main
            custom_authenticator: FOS\OAuthServerBundle\Security\Authentication\Authenticator\OAuthAuthenticator
            stateless: true
```

Keep `fos_oauth_server.service.user_provider` pointing at the corresponding user
provider service, as on the existing Symfony 6.4 application.

## Bundle changes

- Native Symfony and OAuth interface return types, explicit nullable container
  arguments, and `AsCommand` registration for both console commands.
- The current `createToken(Passport, ...)` authenticator API replaces the unused
  `createAuthenticatedToken(PassportInterface, ...)` compatibility method.
- Authorization reads scalar values from query/request bags and submitted arrays
  through `InputBag::all()`. The controller no longer receives the removed session
  service; it reads the session from the request.
- Internal service definitions use PHP configuration to avoid Symfony 7.4's XML
  configuration deprecation. Service IDs, arguments, aliases and database mappings
  remain compatible.
- The form theme uses Twig whitespace control instead of the deprecated
  `spaceless` filter.

Existing XML route imports still work on both supported Symfony versions. To avoid
Symfony 7.4's XML routing deprecation, use the equivalent YAML imports:

```yaml
fos_oauth_server_token:
    resource: '@FOSOAuthServerBundle/Resources/config/routing/token.yaml'

fos_oauth_server_authorize:
    resource: '@FOSOAuthServerBundle/Resources/config/routing/authorize.yaml'
```

## Validation

The bundle suite runs with `composer test` after installing its development
dependencies. The repaired fixtures use the authenticator system, password
hashers and current Doctrine/Symfony APIs. Functional tests use in-memory token
managers and do not need a running database.

Local validation on PHP 8.4.22 uses separate Composer installations for the Symfony
6.4 and 7.4 release lines. Both run the full suite, including ORM container boot,
MongoDB manager tests, console commands, authorization forms, Twig rendering and
an HTTP password-grant / refresh / bearer flow. The HTTP test also verifies scope
rejection and expired/unknown access-token rejection.

| Runtime | Result |
| --- | --- |
| Symfony FrameworkBundle 6.4.49 / HttpKernel 6.4.50 | 117 tests, 315 assertions; passed |
| Symfony 7.4.23 | 117 tests, 315 assertions; passed |

Composer validation, PHP syntax checks and PHPStan level 2 on the changed
production files also pass. An isolated offline Composer dry-run against the
application's current lockfile selects `dev-sf7` with exactly one package update;
all Symfony packages stay at their locked Symfony 6.4 versions.

The PHPUnit configuration permits zero self/direct deprecations. Four indirect
PHPUnit/php-mock compatibility notices remain allowed; they originate in the
existing test tooling.

The isolated matrices retain the application's MongoDB library 2.0.0 / ODM 2.11.3
baseline and the installed MongoDB extension 2.3.3. Composer's security blocking
was disabled only for those temporary test installations to reproduce that
existing baseline. This branch does not resolve the MongoDB dependency advisories
already recorded in the application's Symfony upgrade notes.

A fresh PeopleWeek test-container boot fails with the same missing
`annotation_reader` service on both the original bundle and this branch. With a
shim confined to a temporary diagnostic kernel, the application boots on Symfony
6.4.50 and discovers both OAuth commands from this local branch. The application
Composer files and installed dependencies were not changed. Full application
acceptance on Symfony 7.4 remains part of the application's upgrade work.
