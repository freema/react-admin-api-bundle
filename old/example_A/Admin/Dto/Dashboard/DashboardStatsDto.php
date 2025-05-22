<?php
declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Dto\Dashboard;

use Vlp\Mailer\Api\Admin\Dto\AdminApiDto;

class DashboardStatsDto extends AdminApiDto
{
    public int $totalSent = 0;

    public int $totalSentSuccess = 0;

    public int $totalSentError = 0;

    /** @var MessagesByTemplateDto[] */
    public array $messagesByTemplate = [];

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
            'totalSent' => $this->getTotalSent(),
            'totalSentSuccess' => $this->getTotalSentSuccess(),
            'totalSentError' => $this->getTotalSentError(),
            'messagesByTemplate' => array_map(fn (MessagesByTemplateDto $msg) => $msg->toArray(), $this->messagesByTemplate),
        ];
        if (0 == $this->getTotalSent()) {
            $data['successPercent'] = 0;
        } else {
            if (0 === $this->getTotalSentSuccess() && 0 === $this->getTotalSentError()) {
                $data['successPercent'] = 0;
            } else {
                $data['successPercent'] = round(($this->getTotalSentSuccess() / ($this->getTotalSentSuccess() + $this->getTotalSentError())) * 100, 2);
            }
        }

        return $data;
    }

    public function getTotalSent(): int
    {
        return $this->totalSent;
    }

    public function setTotalSent(int $totalSent): void
    {
        $this->totalSent = $totalSent;
    }

    public function getTotalSentSuccess(): int
    {
        return $this->totalSentSuccess;
    }

    public function setTotalSentSuccess(int $totalSentSuccess): void
    {
        $this->totalSentSuccess = $totalSentSuccess;
    }

    public function getTotalSentError(): int
    {
        return $this->totalSentError;
    }

    public function setTotalSentError(int $totalSentError): void
    {
        $this->totalSentError = $totalSentError;
    }

    public function getMessagesByTemplate(): array
    {
        return $this->messagesByTemplate;
    }

    public function addMessagesByTemplate(MessagesByTemplateDto $messagesByTemplate): void
    {
        $this->messagesByTemplate[] = $messagesByTemplate;
    }
}
