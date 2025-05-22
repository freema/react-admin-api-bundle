# Task Worker Bundle

Symfony bundle pro správu a plánování úloh pomocí cron výrazů.

## Instalace

```bash
composer require vlp/task-worker-bundle
```

## Konfigurace

1. Zaregistrujte bundle v `config/bundles.php`:
```php
return [
    // ...
    VLM\TaskWorkerBundle\TaskWorkerBundle::class => ['all' => true],
];
```

2. Vytvořte konfiguraci `config/packages/vlm_task_worker.yaml`:
```yaml
vlm_task_worker:
    tasks:
        send_verification_reminders:
            class: App\Model\TaskWorker\Task\SendVerificationReminders
            schedule: '0 2 * * *'

        sync_users_data:
            class: App\Model\TaskWorker\Task\SyncUsersDataWithSolar
            schedule: '*/30 * * * *'
            usersCount: 200

        clean_expired_codes:
            class: App\Model\TaskWorker\Task\CleanExpiredGoogleVerificationCode
            schedule: '*/20 * * * *'
            limit: 10000

        sync_missing_users:
            class: App\Model\TaskWorker\Task\SyncMissingSolarUsers
            schedule: '0 */2 * * *'
            batchSize: 100
```

Alternativně můžete registrovat tasky pomocí service tagů:

```yaml
services:
    App\Model\TaskWorker\Task\SendVerificationReminders:
        tags: ['taskWorker.task']
        arguments:
            $schedule: '0 2 * * *'

    App\Model\TaskWorker\Task\SyncUsersDataWithSolar:
        tags: ['taskWorker.task']
        arguments:
            $schedule: '*/30 * * * *'
            $usersCount: 200
```

## Vytvoření nového tasku

1. Vytvořte třídu která dědí z `AbstractTask`:

```php
namespace App\Model\TaskWorker\Task;

use VLM\TaskWorkerBundle\Task\AbstractTask;
use Psr\Log\LoggerInterface;

class SendVerificationReminders extends AbstractTask
{
    public function __construct(
        string $schedule,
        LoggerInterface $logger
    ) {
        parent::__construct($schedule, $logger);
    }

    public function getName(): string
    {
        return 'send_verification_reminders';
    }

    public function run(): array
    {
        // Implementace tasku
        return [
            'status' => 'success',
            'processed' => 100
        ];
    }
}
```

## Spuštění tasků

Bundle poskytuje command pro spouštění tasků:

```bash
# Spustí všechny tasky které jsou due podle jejich cron výrazu
php bin/console task-worker:run
```

Doporučujeme nastavit cron který bude spouštět tento command každých 5 minut:

```crontab
*/5 * * * * php /path/to/your/project/bin/console task-worker:run
```

## Podporované verze

- PHP 8.1+
- Symfony 5.4 / 6.4 / 7.1

## Vývoj a testování

Viz [CONTRIBUTING.md](CONTRIBUTING.md)

## CI/CD Pipeline

### Stages

Pipeline se skládá ze tří hlavních stages:

1. **test** - Základní unit testy
   - PHPUnit testy
   - Coverage report

2. **compatibility** - Testy kompatibility s různými verzemi Symfony
   - Symfony 5.4
   - Symfony 6.4
   - Symfony 7.1

3. **quality** - Kontroly kvality kódu
   - PHPStan (level 8)
   - PHP CS Fixer
   - Composer validace
   - Kontrola závislostí

### Cache a Artifacts

- **Cache**
   - Composer dependencies
   - Vendor adresář
   - Cache specifická pro každou větev

- **Artifacts**
   - PHPUnit coverage report
   - PHPStan report
   - CS Fixer report
   - Dokumentace (GitLab Pages)

### Proxy konfigurace

Pipeline je nakonfigurována pro použití s firemním proxy serverem:
- HTTP Proxy: proxy.vlp.cz:3128
- HTTPS Proxy: proxy.vlp.cz:3128