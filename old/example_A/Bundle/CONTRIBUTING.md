# Contributing to Task Worker Bundle

## Vývojové prostředí

Projekt používá Docker pro vývoj. Pro začátek vývoje:

1. Naklonujte repositář:
```bash
git clone https://code.denik.cz/packagist/task-worker-bundle.git
cd task-worker-bundle
```

2. Spusťte vývojové prostředí:
```bash
task up      # nebo task u
```

3. Nainstalujte závislosti:
```bash
task composer:install    # nebo task c:i
```

## Struktura projektu

```
task-worker-bundle/
├── src/                    # Zdrojové kódy bundle
│   ├── Command/           # Symfony commands
│   ├── DependencyInjection/
│   ├── Executor/         # Task executory
│   ├── Task/             # Základní třídy pro tasky
│   └── Resources/        # Konfigurace a služby
├── dev/                   # Vývojové prostředí
│   ├── config/           # Testovací konfigurace
│   └── DevKernel.php     # Kernel pro vývoj
├── tests/                 # Unit a funkcionální testy
└── test/                  # Test různých verzí Symfony
    ├── symfony54/
    ├── symfony64/
    └── symfony71/
```

## Dostupné příkazy

### Docker Management
```bash
task up        # Start development environment (alias: u)
task down      # Stop development environment (alias: d)
task restart   # Restart development environment (alias: r)
```

### Development
```bash
task php:shell   # Open shell in PHP container (alias: sh)
task dev:serve   # Start development server (alias: serve)
```

### Composer
```bash
task composer:install   # Install dependencies (alias: c:i)
task composer:update   # Update dependencies (alias: c:u)
```

### Testing
```bash
task test              # Run PHPUnit tests (alias: t)
task test:symfony54    # Test with Symfony 5.4 (alias: t:54)
task test:symfony64    # Test with Symfony 6.4 (alias: t:64)
task test:symfony71    # Test with Symfony 7.1 (alias: t:71)
```

### Code Quality
```bash
task stan             # Run PHPStan analysis (alias: lint)
task cs:fix           # Fix code style (alias: fix)
```

## Vývoj nových funkcí

1. Vytvořte novou větev:
```bash
git checkout -b feature/nova-funkce
```

2. Přidejte testy pro novou funkcionalitu
3. Implementujte funkcionalitu
4. Spusťte testy a kontroly kvality:
```bash
task stan            # PHPStan analýza
task cs:fix          # Code style
task test:symfony54  # Test se Symfony 5.4
task test:symfony64  # Test se Symfony 6.4
task test:symfony71  # Test se Symfony 7.1
```

5. Commitněte změny a vytvořte merge request

## Testování v /dev prostředí

Adresář `/dev` obsahuje testovací prostředí s ukázkovými tasky. Pro testování:

1. Spusťte dev server:
```bash
task dev:serve
```

2. Zkuste spustit ukázkový task:
```bash
cd dev
php index.php task-worker:run
```

## Testování s různými verzemi Symfony

Bundle podporuje Symfony 5.4, 6.4 a 7.1. Pro testování kompatibility:

```bash
# Test jedné verze
task test:symfony54
task test:symfony64
task test:symfony71

# Vyčištění po testech
task test:cleanup
```

## Debugování

Pro debugování můžete použít:

1. Xdebug je k dispozici v kontejneru
2. Monolog je nakonfigurován v dev prostředí
3. Symfony Debug Bundle je k dispozici v dev prostředí

## Vytváření release

1. Aktualizujte verzi v `composer.json`
2. Vytvořte changelog
3. Vytvořte tag
4. Push na GitLab

## Code Style

Projekt používá PSR-12 a další pravidla definovaná v `.php-cs-fixer.dist.php`. Pro kontrolu:

```bash
task cs:fix
```