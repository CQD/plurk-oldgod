<?php

include __DIR__ . '/../vendor/autoload.php';

define('NO_QLOG', true);

class DummyQlurk
{
    public $history = [];
    protected $prepare = [];

    public function call(string $endpoint, array $params = []): array
    {
        $response = ($this->prepare) ? array_shift($this->prepare) : [];
        $this->history[] = [
            'endpoint' => $endpoint,
            'params' => $params,
            'response' => $response,
        ];

        return $response;
    }

    public function prepare($response)
    {
        $this->prepare[] = $response;
    }
}
