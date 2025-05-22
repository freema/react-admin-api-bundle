<?php
declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Dto\Dashboard;

use Vlp\Mailer\Api\Admin\Dto\AdminApiDto;
use Vlp\Mailer\Service\MetricService;

class ChartDto extends AdminApiDto
{
    public \DateTime $timestamp;

    public int $count = 0;

    public int $sentCount = 0;

    public int $failCount = 0;

    public string $type;

    public static function getMappedEntityClass(): string
    {
        return '';
    }

    public static function createFromEntity($entity): AdminApiDto
    {
        return new self();
    }

    public function toArray(): array
    {
        $data = [
            'count' => $this->getCount(),
            'sentCount' => $this->getSentCount(),
            'failCount' => $this->getCount() - $this->getSentCount(),
        ];

        switch (MetricService::CHART_SHOW[$this->type]) {
            case MetricService::CHART_TYPE_HOUR:
                $data['timestamp'] = $this->timestamp->format('H');
                $data['timestampShow'] = $this->timestamp->format('H');
                break;
            case MetricService::CHART_TYPE_DAY:
                $data['timestamp'] = $this->timestamp->format('Y-m-d');
                $data['timestampShow'] = $this->timestamp->format('d. m. Y');
                break;
            case MetricService::CHART_TYPE_MONTH:
                $data['timestamp'] = $this->timestamp->format('m');
                $data['timestampShow'] = $this->timestamp->format('M');
                break;
        }

        return $data;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): void
    {
        $this->count = $count;
    }

    public function getSentCount(): int
    {
        return $this->sentCount;
    }

    public function setSentCount(int $sentCount): void
    {
        $this->sentCount = $sentCount;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTime $timestamp): void
    {
        $this->timestamp = $timestamp;
    }
}
