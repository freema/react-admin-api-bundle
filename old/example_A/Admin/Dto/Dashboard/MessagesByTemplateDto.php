<?php

namespace Vlp\Mailer\Api\Admin\Dto\Dashboard;

class MessagesByTemplateDto
{
    public function __construct(
        public readonly int $templateId,
        public readonly int $campaignId,
        public readonly string $campaignSlug,
        public readonly int $sentCount,
    ) {
    }

    public function toArray(): array
    {
        return [
            'templateId' => $this->templateId,
            'campaignId' => $this->campaignId,
            'campaignSlug' => $this->campaignSlug,
            'sentCount' => $this->sentCount,
        ];
    }
}
