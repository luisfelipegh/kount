<?php

namespace PlacetoPay\Kount\Messages\Responses;

use PlacetoPay\Kount\Constants\DecisionCodes;

class InquiryOrder extends CreateOrder
{
    public function omniscore(): ?float
    {
        return $this->get('order.riskInquiry.omniscore');
    }

    public function decision(): ?string
    {
        return $this->get('order.riskInquiry.decision');
    }

    public function shouldApprove(): bool
    {
        return $this->get('order.riskInquiry.decision') === DecisionCodes::APPROVE;
    }

    public function shouldDecline(): bool
    {
        return $this->get('order.riskInquiry.decision') === DecisionCodes::DECLINE;
    }

    public function shouldReview(): bool
    {
        return $this->get('order.riskInquiry.decision') === DecisionCodes::REVIEW;
    }

    public function rulesTriggered(): array
    {
        return $this->get('order.riskInquiry.segmentExecuted.policiesExecuted', []);
    }
}
