<?php

namespace PlacetoPay\Kount\Messages\Responses;

use GuzzleHttp\Psr7\Response;
use PlacetoPay\Kount\Exceptions\KountServiceException;

class Base
{
    protected Response $response;
    protected array $data = [];

    /**
     * @throws KountServiceException
     */
    public function __construct(Response $response)
    {
        $this->response = $response;

        $this->decodeJson();
    }

    protected function decodeJson(): void
    {
        try {
            $body = $this->response->getBody()->getContents();

            $this->data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new KountServiceException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $array = $this->data;

        foreach ($keys as $key) {
            if (is_array($array) && array_key_exists($key, $array)) {
                $array = $array[$key];
            } else {
                return $default;
            }
        }

        return $array;
    }

    public function status(): int
    {
        return $this->response->getStatusCode();
    }

    public function successful(): bool
    {
        return ($this->status() >= 200 && $this->status() < 300) || empty($this->get('error')) || empty($this->get('errors'));
    }

    public function raw(): string
    {
        return (string)$this->response->getBody();
    }

    public function errors(): ?array
    {
        return $this->get('error') ?? null;
    }
}
