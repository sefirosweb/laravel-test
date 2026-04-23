# laravel-test

Banco de pruebas interno para los 5 paquetes Laravel de `sefirosweb`. **No es una aplicación de producción** — solo sirve como host real para validar que los paquetes arrancan correctamente bajo cada major de Laravel.

## Estructura

- **Host**: Laravel 12 + [Laravel Sail](https://laravel.com/docs/sail) (Docker).
- **Paquetes bajo prueba** (`packages/`, cada uno es un submódulo git independiente):

  | Paquete | Repo |
  |---|---|
  | laravel-access-list | [github.com/sefirosweb/laravel-access-list](https://github.com/sefirosweb/laravel-access-list) |
  | laravel-cronjobs | [github.com/sefirosweb/laravel-cronjobs](https://github.com/sefirosweb/laravel-cronjobs) |
  | laravel-general-helper | [github.com/sefirosweb/laravel-general-helper](https://github.com/sefirosweb/laravel-general-helper) |
  | laravel-mailing | [github.com/sefirosweb/laravel-mailing](https://github.com/sefirosweb/laravel-mailing) |
  | laravel-odoo-connector | [github.com/sefirosweb/laravel-odoo-connector](https://github.com/sefirosweb/laravel-odoo-connector) |

## Modelo de ramas (major-aligned)

Cada repo (tanto este host como los submódulos) sigue el mismo patrón que usa el propio `laravel/framework`:

- **Ramas nombradas por major de Laravel** (`12.x`, `11.x`, `9.x`, …).
- **Default** = major actualmente soportado.
- **Sin `master`** — cada versión vive en su rama con nombre.
- Tags: `v<major>.<minor>.<patch>` (SemVer).

### Estado actual

| Repo | Default | Ramas | Tag actual |
|---|---|---|---|
| laravel-test (host) | `12.0` | `9.0`, `11.0`, `12.0` | — |
| 5 submódulos | `12.x` | `9.x`, `12.x` | `v12.0.0` |

- Para trabajar en L12: `git checkout 12.x` (o `12.0` en el host).
- Para hotfix en L9 legacy: `git checkout 9.x` + nuevo tag `v9.x.y`.

## Arrancar el entorno

```bash
./vendor/bin/sail up -d
```

- App en `http://localhost:80`
- MySQL en `localhost:3306` (sail / password)

Si venías de un `vendor/` antiguo (antes de Laravel 12), regenera:

```bash
./vendor/bin/sail composer install
./vendor/bin/sail restart laravel.test
```

## Trabajar sobre un paquete

Cada submódulo tiene su propio `composer.json`, `vendor/` y suite Testbench independiente.

```bash
# Instalar/actualizar dependencias del paquete
docker exec -w /var/www/html/packages/<paquete> laravel-test-laravel.test-1 composer update

# Correr los tests del paquete
docker exec -w /var/www/html/packages/<paquete> laravel-test-laravel.test-1 ./vendor/bin/phpunit
```

Primera vez que entras a un submódulo desde el contenedor, si git se queja de `dubious ownership`:

```bash
docker exec laravel-test-laravel.test-1 \
  git config --global --add safe.directory /var/www/html/packages/<paquete>
```

## Flujo de release (cuando subas un paquete a una nueva major de Laravel)

Ejemplo: portar `laravel-cronjobs` a Laravel 13.

```bash
cd packages/laravel-cronjobs
git checkout 12.x
git checkout -b 13.x          # se crea desde 12.x
# ... aplicar cambios, bumpear composer.json a ^13.0, correr tests ...
git commit -m "Add Laravel 13 support"
git tag v13.0.0
git push -u origin 13.x
git push origin v13.0.0
```

Luego en GitHub UI: **Settings → Branches → Default branch → Switch to `13.x`**.

La rama `12.x` queda congelada (solo fix-only). Tag `v12.0.0` sigue instalable en proyectos L12 existentes.

## Documentación técnica detallada

Ver [CLAUDE.md](CLAUDE.md) para:
- Breaking changes de Laravel 11→12 aplicados y dónde
- Setup estándar de Testbench para nuevos paquetes
- Pendientes conocidos (phpspreadsheet EOL, `utf8_decode`, dynamic properties)
