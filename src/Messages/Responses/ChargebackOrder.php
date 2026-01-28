<?php

namespace PlacetoPay\Kount\Messages\Responses;

class ChargebackOrder extends Base
{
    public function successful(): bool
    {
        return parent::successful() && empty($this->errors());
    }

    public function errors(): array
    {
        return $this->get('errors', []);
    }
}
