# Phel CLI Skeleton

[Phel](https://phel-lang.org/) is a functional Lisp that compiles to PHP. This
repository is a minimal, opinionated starting point for building a real **CLI
application** with Phel.

It ships:

- A data-driven command dispatcher built on top of [`phel\cli`](https://phel-lang.org/)
  (a thin wrapper over `symfony/console`) with two sample subcommands:
  `greet` and `add`.
- An exportable Phel module (`adder-module`) consumed from PHP via the
  auto-generated `PhelGenerated\CliSkeleton\Modules\AdderModule` class.
- Tests that exercise both pure logic and the CLI handler boundary using
  `phel\cli`'s built-in test helpers.

## Requirements

- PHP **>= 8.4** ([phpbrew](https://github.com/phpbrew/phpbrew) on Linux,
  [shivammathur/homebrew-php](https://github.com/shivammathur/homebrew-php) on macOS)
- [Composer](https://getcomposer.org/)

> A `build/Dockerfile` and `docker-compose.yml` are included if you'd rather not
> install PHP locally.

## Getting started

```bash
git clone <this repo>
cd cli-skeleton
composer install
```

### Run the CLI from sources

```bash
composer dev                          # invoke default command (greet)
vendor/bin/phel run cli-skeleton.main greet alice --loud
vendor/bin/phel run cli-skeleton.main add 1 2 3
```

### Compile a standalone PHP code

```bash
composer build                        # → out/main.php
php out/main.php greet alice
php out/main.php add 1 2 3
```

### Run the tests

```bash
composer test
```

### Format

```bash
composer format
```

### REPL

```bash
composer repl
```

## Project layout

```
src/
├── main.phel                         ; wires the Application + dispatches
├── commands/
│   ├── greet.phel                    ; subcommand: greet
│   └── adder.phel                    ; subcommand: add
├── modules/
│   └── adder-module.phel             ; pure logic, exported to PHP
└── PhelGenerated/                    ; auto-generated PHP wrappers
tests/
├── commands/                         ; CLI handler smoke tests
└── modules/                          ; pure unit tests
example/
└── using-exported-phel-function.php  ; call a Phel fn from PHP
phel-config.php                       ; build / export / format config
```

### Adding a new command

1. Create `src/commands/<name>.phel` exposing a `def <name>-command` map (see
   `greet.phel` for the spec — `phel\cli` docs at
   `vendor/phel-lang/phel-lang/docs/cli-guide.md` cover every option).
2. Register it in `src/main.phel` by adding it to the `:commands` vector.
3. Drop a test in `tests/commands/<name>-test.phel` (use `cli/argv` +
   `cli/buffered-output` to drive the handler in-process).

### Exporting Phel functions to PHP

Mark a function with `{:export true}` (see `src/modules/adder-module.phel`),
then run:

```bash
composer export    # regenerates src/PhelGenerated/**
php example/using-exported-phel-function.php
```

## Docker

```bash
docker compose up -d --build
docker exec -ti -u dev phel_cli_skeleton bash
composer install
```

## More

- Phel docs: <https://phel-lang.org/documentation/getting-started/>
- `phel\cli` guide: `vendor/phel-lang/phel-lang/docs/cli-guide.md`
