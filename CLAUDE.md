# CLAUDE.md

Proyecto Laravel 13 usado como banco de pruebas para los paquetes internos de la empresa (submódulos en `packages/`).

## Estructura

- **App host** (`laravel-test`): Laravel 13 + Sail. Solo sirve para arrancar los paquetes en un entorno real. No se desarrolla aplicación aquí.
- **Submódulos git** en `packages/` — cada uno es un repo independiente en `github.com/sefirosweb/…`:
  - `laravel-access-list`
  - `laravel-cronjobs`
  - `laravel-general-helper`
  - `laravel-mailing`
  - `laravel-odoo-connector`
- Solo `laravel-odoo-connector` está autoloaded desde el `composer.json` del host. Los demás se prueban dentro de su propio submódulo vía Testbench.

## Política de versionado de paquetes

**Major aligned con Laravel** (como Spatie, Barryvdh, Orchestra) — decisión tomada 2026-04-23, sin retrocompatibilidad:

- Ramas nombradas por major de Laravel (`13.x`, `12.x`, `11.x`, `9.x`, …). **No hay rama `master`** — se borró en 2026-04-23 al migrar al esquema versionado.
- Default branch de cada repo = major actualmente soportado (hoy `13.x` en los 5 submódulos, `13.0` en el host). Se cambia el default branch en GitHub UI al hacer el bump.
- Código L9 legacy vive en la rama `9.x` de cada submódulo (heredada del viejo `master`). Los tags `1.x` anteriores corresponden a esa línea.
- Tag por paquete: `v<major>.<minor>.<patch>` (SemVer). Primer tag L13 = `v13.0.0`. Tag final L12 = `v12.0.3`.
- `composer.json` → `"laravel/framework": "^13.0"` y `"php": "^8.3"` (constraint estricta, no rango multi-versión).
- Al subir a L14: rama `14.x` creada desde `13.x`, tag `v14.0.0`, default branch cambia a `14.x` vía GitHub UI. La rama `13.x` queda congelada (fix-only urgente).
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

- `composer.json` con `require-dev`: `orchestra/testbench ^11.0`, `phpunit/phpunit ^12.0`
- `autoload-dev.psr-4`: `"Sefirosweb\\<Package>\\Tests\\": "tests/"`
- `phpunit.xml` con bootstrap `vendor/autoload.php` y suites `Unit`/`Feature`
- `tests/TestCase.php` extiende `Orchestra\Testbench\TestCase`, registra el SP del paquete en `getPackageProviders()`, configura SQLite `:memory:` en `defineEnvironment()`. Si las migraciones del paquete dependen de una tabla `users` del host, crearla en `defineDatabaseMigrations()`.
- `.gitignore` con `/vendor`, `.phpunit.cache`, `composer.lock`

## Estado de los paquetes (Laravel 13)

| Paquete | Tests | Estado |
|---|---|---|
| `laravel-access-list` | 17 | ✅ L13 + PHP 8.3 + PHPUnit 12 + Testbench 11 |
| `laravel-cronjobs` | 22 | ✅ L13 + PHP 8.3 + PHPUnit 12 + Testbench 11 |
| `laravel-general-helper` | 60 | ✅ L13 + dompdf `^3.1.2` (única que soporta L13) + phpspreadsheet `^3.0` |
| `laravel-mailing` | 18 | ✅ L13 + PHP 8.3 + PHPUnit 12 + Testbench 11 |
| `laravel-odoo-connector` | 26 (24 skipped sin ENV) | ✅ L13 + fix `OdooConnection::select` signature (4º param `array $fetchUsing = []`) |

**Total Feature/Unit: ~143 tests verdes.** Los 24 Integration de odoo-connector requieren `ODOO_HOST/ODOO_DB/ODOO_USERNAME/ODOO_PASSWORD` en env y se skipean automáticamente sin ellos.

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

## Breaking changes de Laravel 12→13 aplicados (2026-05-09)

| Pattern | Dónde | Fix aplicado |
|---|---|---|
| `Connection::select` añadió 4º parámetro `array $fetchUsing = []` | `laravel-odoo-connector/Database/OdooConnection.php` | Añadido `array $fetchUsing = []` al override. Sin el fix la clase fallaba **al cargar** (no al instanciar) por LSP+`strict_types=1` → `Premature end of PHP process`. Aviso: el error no era una excepción capturable, era un fatal del engine. |
| `php ^8.2` → `^8.3` | Todos los `composer.json` (host + 5 paquetes) | testbench 11 exige PHP 8.3. PHP 8.4 sigue siendo soportado (Sail runtime usa 8.4). |
| `laravel/framework: ^12.0` → `^13.0` | Todos los `composer.json` | — |
| `orchestra/testbench: ^10.0` → `^11.0` | Los 5 paquetes (require-dev) | testbench 11.0+ pivota a L13 |
| `phpunit/phpunit: ^11.0` → `^12.0` | Todos los `composer.json` | — |
| `laravel/tinker: ^2.10.1` → `^3.0` | Host | tinker 3 soporta L13 |
| `laravel/sail: ^1.44` → `^1.58` | Host | sail 1.58.0 es la primera con `illuminate/console ^13.0` |
| `barryvdh/laravel-dompdf: ^3.0` → `^3.1.2` | `laravel-general-helper` | **v3.1.2 es la única versión 3.x que añade `^13.0` a illuminate/support**. v3.0/3.1.0/3.1.1 NO soportan L13. |
| `phpoffice/phpspreadsheet: ^3.0` | sin cambio | v3 sigue funcionando con L13 + PHP 8.3. v4/v5 disponibles si surgen necesidades, pero no son requisito. |

### Lo que **no** hubo que tocar (verificado por grep):

- `VerifyCsrfToken → PreventRequestForgery`: ningún paquete extendía o referenciaba esa middleware. El alias deprecated del framework cubre los `withoutMiddleware([VerifyCsrfToken::class])` en vendor/.
- `JobAttempted::$exceptionOccurred → $exception`: ningún paquete escucha ese evento.
- `QueueBusy::$connection → $connectionName`: idem.
- `upsert($values, $uniqueBy)` empty validation: ningún paquete usa `->upsert(`.
- `Manager::extend()` callback rebound: solo aparece en `DB::extend('odoo', ...)` de odoo-connector, pero la closure no usa `$this`, así que el rebind es transparente.
- `Cache::touch()` en custom Store implementations: ningún paquete implementa un cache store custom.
- Model `boot*()` con instanciación nested: los dos boots en código propio (`bootSelfModelValidator`, `bootSoftDeletes`) solo registran callbacks/scopes, no instancian el modelo.

## Estado a día de hoy (rama 13.x — pendiente de tag v13.0.0)

Estado local en la rama 13.x de cada paquete (5) y 13.0 del host. **NO se han pusheado ni tageado todavía** — el usuario hace push/tag desde su flujo. La GitHub Action CI hay que actualizarla a matrix PHP 8.3 / 8.4 y a la rama 13.x.

Acumulativo desde v12.0.x:
- **Laravel 13.8 + PHP 8.3+** (Sail container ya corre PHP 8.4).
- **PHPUnit 12 + Testbench 11** en todos los paquetes.
- Los breaking changes L12→L13 ya aplicados (ver tabla arriba). Único cambio en código real: `OdooConnection::select` signature.
- App host responde HTTP 200, tests del host (2/2) verdes.

### Pendientes para tag v13.0.0

- Actualizar `.github/workflows/*.yml` de cada paquete: cambiar la matrix a PHP 8.3 / 8.4 y la rama target a `13.x`.
- Push de las ramas 13.x + tags v13.0.0 en los 5 paquetes.
- Cambiar el default branch en GitHub UI a `13.x` para cada paquete (y `13.0` para el host si aplica).
- Ejecutar Integration suite de odoo-connector con ENV vars reales (`ODOO_HOST/ODOO_DB/ODOO_USERNAME/ODOO_PASSWORD`) antes de tagear.

## Pendientes reales para v13.0.0 o posterior

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
