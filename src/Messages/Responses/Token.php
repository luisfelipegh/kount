<?php

namespace PlacetoPay\Kount\Messages\Responses;

class Token extends Base
{
    public function expiresIn(): ?int
    {
        return $this->get('expires_in');
    }

    public function accessToken(): ?string
    {
        return $this->get('access_token');
    }

    public function tokenType(): ?string
    {
        return $this->get('token_type');
    }

    public function scope(): ?string
    {
        return $this->get('scope');
    }
}
