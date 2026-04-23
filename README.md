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

## Requisitos

- **Docker** (+ Docker Compose) — Sail lo usa por debajo.
- **Git** con acceso SSH a `github.com/sefirosweb` (los submódulos se clonan por SSH).
- Puerto **80** y **3306** libres en local (Sail los publica).

No hace falta PHP ni Composer en el host; todo corre dentro del contenedor.

## Clonar el proyecto

El proyecto usa submódulos git — hay que clonar recursivamente:

```bash
git clone --recurse-submodules git@github.com:sefirosweb/laravel-test.git
cd laravel-test
```

Si ya hiciste `git clone` sin `--recurse-submodules`, trae los submódulos ahora:

```bash
git submodule update --init --recursive
```

Después de un `git pull` que incluya cambios en los pointers de submódulo, refréscalos:

```bash
git submodule update --init --recursive
```

## Arrancar el entorno

```bash
./vendor/bin/sail up -d
```

- App en `http://localhost:80`
- MySQL en `localhost:3306` (usuario: `sail`, password: `password`)

Si venías de un `vendor/` antiguo (por ejemplo si saltaste de la rama `9.0`/`11.0` a `12.0`), regenera:

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

### Correr tests de todos los paquetes de una pasada

```bash
for pkg in laravel-access-list laravel-cronjobs laravel-general-helper laravel-mailing laravel-odoo-connector; do
  echo "=== $pkg ==="
  docker exec -w /var/www/html/packages/$pkg laravel-test-laravel.test-1 ./vendor/bin/phpunit --no-coverage
done
```

## Consumo desde tu app de producción

Los paquetes se publican en [Packagist](https://packagist.org/packages/sefirosweb/) vía sus tags `v<major>.<minor>.<patch>`. En el `composer.json` de la app de producción:

```json
{
    "require": {
        "sefirosweb/laravel-access-list": "^12.0",
        "sefirosweb/laravel-cronjobs": "^12.0",
        "sefirosweb/laravel-general-helper": "^12.0",
        "sefirosweb/laravel-mailing": "^12.0",
        "sefirosweb/laravel-odoo-connector": "^12.0"
    }
}
```

Luego `composer update sefirosweb/*`. Al pasar a Laravel 13 en producción, cambias el constraint a `^13.0` cuando cada paquete tenga su tag `v13.0.0`.

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

## Troubleshooting

### `./vendor/bin/sail up` falla por puerto 80 ocupado

Cambia el puerto en `.env` antes de arrancar:

```bash
echo 'APP_PORT=8080' >> .env
./vendor/bin/sail up -d
```

La app quedará en `http://localhost:8080`.

### Git se queja de `dubious ownership` dentro de un submódulo

La primera vez que Composer/PHPUnit entran a un submódulo desde el contenedor, git puede rechazar el directorio por ownership:

```bash
docker exec laravel-test-laravel.test-1 \
  git config --global --add safe.directory /var/www/html/packages/<paquete>
```

### Los submódulos aparecen vacíos tras clonar

Clonaste sin `--recurse-submodules`. Arreglar con:

```bash
git submodule update --init --recursive
```

### PHP-FPM no arranca tras cambiar de rama (`Illuminate\Foundation\Application::configure does not exist`)

El `vendor/` se quedó en una versión vieja de Laravel. Regenerar dentro del contenedor:

```bash
./vendor/bin/sail composer install
./vendor/bin/sail restart laravel.test
```

## Documentación técnica detallada

[CLAUDE.md](CLAUDE.md) contiene notas internas para mantenedores (y para agentes IA que colaboran en el repo): breaking changes de Laravel aplicados, setup de Testbench para nuevos paquetes, pendientes conocidos. Es documentación viva del repo, no pensada para consumidores externos de los paquetes.
