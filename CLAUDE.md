# CLAUDE.md

Proyecto Laravel 12 usado como banco de pruebas para los paquetes internos de la empresa (submódulos en `packages/`).

## Estructura

- **App host** (`laravel-test`): Laravel 12 + Sail. Solo sirve para arrancar los paquetes en un entorno real. No se desarrolla aplicación aquí.
- **Submódulos git** en `packages/` — cada uno es un repo independiente en `github.com/sefirosweb/…`:
  - `laravel-access-list`
  - `laravel-cronjobs`
  - `laravel-general-helper`
  - `laravel-mailing`
  - `laravel-odoo-connector`
- Solo `laravel-odoo-connector` está autoloaded desde el `composer.json` del host. Los demás se prueban dentro de su propio submódulo vía Testbench.

## Política de versionado de paquetes

**Major aligned con Laravel** (como Spatie, Barryvdh, Orchestra) — decisión tomada 2026-04-23, sin retrocompatibilidad:

- Ramas nombradas por major de Laravel (`12.x`, `11.x`, `9.x`, …). **No hay rama `master`** — se borró en 2026-04-23 al migrar al esquema versionado.
- Default branch de cada repo = major actualmente soportado (hoy `12.x` en los 5 submódulos, `12.0` en el host).
- Código L9 legacy vive en la rama `9.x` de cada submódulo (heredada del viejo `master`). Los tags `1.x` anteriores corresponden a esa línea.
- Tag por paquete: `v<major>.<minor>.<patch>` (SemVer). Primer tag L12 = `v12.0.0`.
- `composer.json` → `"laravel/framework": "^12.0"` y `"php": "^8.2"` (constraint estricta, no rango multi-versión).
- Al subir a L13: rama `13.x` creada desde `12.x`, tag `v13.0.0`, default branch cambia a `13.x` vía GitHub UI. La rama `12.x` queda congelada (fix-only urgente).
- Bugfixes retro van a la rama de la versión correspondiente.
- Las versiones antiguas del paquete NO van a instalarse en Laravel nuevas — romper backward compat está aceptado.

## Workflow de desarrollo

### Arrancar el host

```bash
./vendor/bin/sail up -d
```

App en `http://localhost:80`. Si `vendor/` viene de una instalación antigua, correr `./vendor/bin/sail composer install` y `sail restart laravel.test`.

### Trabajar dentro de un submódulo

Cada submódulo tiene su propio `composer.json`, `vendor/`, y tests independientes. Para instalar/actualizar deps y correr tests:

```bash
docker exec -w /var/www/html/packages/<paquete> laravel-test-laravel.test-1 composer update
docker exec -w /var/www/html/packages/<paquete> laravel-test-laravel.test-1 ./vendor/bin/phpunit
```

Primera vez que se entra a un submódulo desde el contenedor, git puede quejarse de `dubious ownership` — se resuelve con:

```bash
docker exec laravel-test-laravel.test-1 git config --global --add safe.directory /var/www/html/packages/<paquete>
```

## Setup de tests en paquetes

Patrón estándar **Orchestra Testbench**. Archivos por paquete:

- `composer.json` con `require-dev`: `orchestra/testbench ^10.0`, `phpunit/phpunit ^11.0`
- `autoload-dev.psr-4`: `"Sefirosweb\\<Package>\\Tests\\": "tests/"`
- `phpunit.xml` con bootstrap `vendor/autoload.php` y suites `Unit`/`Feature`
- `tests/TestCase.php` extiende `Orchestra\Testbench\TestCase`, registra el SP del paquete en `getPackageProviders()`, configura SQLite `:memory:` en `defineEnvironment()`. Si las migraciones del paquete dependen de una tabla `users` del host, crearla en `defineDatabaseMigrations()`.
- `.gitignore` con `/vendor`, `.phpunit.cache`, `composer.lock`

## Estado de los paquetes (Laravel 12)

| Paquete | Tests | Estado |
|---|---|---|
| `laravel-access-list` | 5 | ✅ Testbench, rutas FQCN, migrations anónimas, middleware alias fix |
| `laravel-cronjobs` | 6 | ✅ Testbench, rutas FQCN, Carbon 3 fix |
| `laravel-general-helper` | 4 | ✅ Testbench, rutas FQCN, dompdf ^3, PdfHelper con property tipada, migration anónima |
| `laravel-mailing` | 3 | ✅ Testbench, rutas FQCN |
| `laravel-odoo-connector` | 2 | ✅ Testbench (tests viejos eliminados por mal hechos según autor) |

**Total: 20 tests, 36 assertions, todos verdes.**

## Breaking changes de Laravel 11→12 aplicados

| Pattern | Dónde | Fix aplicado |
|---|---|---|
| `barryvdh/laravel-dompdf` v2 → v3 | `laravel-general-helper/composer.json` | Bump `^3.0`. API pública (`loadView`, `setPaper`, `stream`, `download`, `save`, `getDomPDF()->set_option()`) no cambia. `app('dompdf.wrapper')` sigue siendo el binding correcto |
| `Carbon::diffIn*()` | `laravel-cronjobs/.../CronjobsController.php:178` | En Carbon 3 devuelve **float Y signado** (no absolute como en Carbon 2). Fix: `(int) $a->diffInSeconds($b, true)` — el flag `absolute: true` es OBLIGATORIO si quieres el comportamiento de Carbon 2, el cast a `(int)` solo no basta |
| Rutas con string controller `'Ctrl@method'` | `access-list`, `cronjobs`, `general-helper`, `mailing` — todas las `routes/web.php` | Reescritas con FQCN `[Ctrl::class, 'method']`. Se eliminó el `Route::group(['namespace' => '...'])` wrapper (ya no se usa en L11+; el SP sigue envolviendo con prefix/middleware). 68 rutas migradas |
| Migrations con clase nombrada | `access-list` (4 archivos) + `general-helper` (1 archivo) | Convertidas a `return new class extends Migration {…}` (standard L9+) |
| Middleware alias registrado con string literal `'FQCN::class'` | `laravel-access-list/LaravelAccessListServiceProvider.php:25` | Cambiado a `CheckACLMiddleware::class` (const, no string). El string literal NO interpola `::class`, era bug silencioso |
| Dynamic properties deprecated (PHP 8.2+) | `laravel-general-helper/Helpers/PdfHelper.php` | Declarada `protected DomPdfWrapper $pdf`. Otros helpers (`ExcelHelper`, `CacheRequest`, `RedisHelper`) tienen el mismo patrón — ver pendientes |
| `Storage::disk('local')` sin root | No se usa en ningún paquete | — |
| `$request->mergeIfMissing()` con claves con punto | No se usa en ningún paquete | — |

## Notas sobre Testbench

- En L11+ el controller base `App\Http\Controllers\Controller` existe como clase vacía en el skeleton de Testbench, por eso los controllers que lo extienden desde paquetes siguen funcionando en los tests.
- Si el paquete `access-list` tiene middleware `checkAcl:acl_edit` en su config, hay que sobrescribirlo a `'web'` en `defineEnvironment()` del TestCase para que las rutas registren sin auth setup.
- Las migraciones con FK a `users` requieren crear la tabla `users` en `defineDatabaseMigrations()` del TestCase.

## Estado a día de hoy (v12.0.2)

Los 5 paquetes publicados con tag `v12.0.2` incluyen, acumulativamente:

- **Laravel 12 + PHP 8.2+** strict-typed en todo `src/` (y en `tests/`).
- **Testbench + Orchestra** → 134+ tests totales (access-list 17, cronjobs 22, general-helper 56, mailing 18, odoo-connector 23 con 21 Integration contra Odoo real).
- **GitHub Actions CI** corriendo en matrix PHP 8.2 / 8.3 / 8.4 por cada push/PR a `12.x`.
- **dompdf v3** + **phpspreadsheet v3** (bumps + migración de APIs removidas).
- **Fix `utf8_decode` → `mb_convert_encoding`** (PHP 9-safe).
- **Race condition fixes** en nombres de archivo (`uniqid()` en vez de `date('YmdHis')`).
- **Helpers tipados** y clases internas de odoo-connector con namespace corregido (`Database\Relations\`).
- **CHANGELOG + LICENSE** en cada paquete.
- **v12.0.2 fix**: `ExcelHelper::getSpreadsheet()` / `getWriter()` públicos tras el breaking change accidental de v12.0.1.

## Pendientes reales para v12.0.3 o posterior

### Menor (bug real pero con impacto casi nulo)

- **`laravel-odoo-connector/Commands/TestOdooConnection.php:53`**: `dd($a->toArray())` en el comando `test:odoo` mata el proceso antes de devolver `Command::SUCCESS`. Reemplazar por `$this->info(json_encode(...))`. ~2 min de trabajo.

### Cosméticos (no bloquean nada)

- Return types nativos en métodos sin ellos: `PdfHelper` (5 métodos), `MailingList::get()`, `SelfModelValidator` (2 métodos).
- Error checks en `fopen()` dentro de `GeneralHelperFunctions.php:338, 394`. Riesgo real muy bajo porque `File::makeDirectory()` ya valida permisos aguas arriba.
- Tests para los comandos utility `purge:temp` y `test:odoo`. Ambos son triviales; difícil justificar cobertura.

### No aplicables / falsos positivos

- **`test()`, `test_timeout()`, `test_error()` en `CronjobsController`**: no es dead code. Son métodos invocables desde la UI del paquete para validar scheduler / queue / timeout. `test_timeout()` es un `while(true)` deliberado para probar que el worker kill del timeout (120s) funciona en producción.
- **`CacheRequest` static state**: intencional. En queue workers largos sí hay leak entre jobs, pero está documentado como "per-request".
- **`DB::raw()` con `IF(ISNULL(...))` en `ListCronjobs::handle`**: MySQL-only intencional. Solo corre en artisan contra la BD de prod.
