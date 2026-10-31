<?php

namespace Fwhy\Blast\Server;

readonly class ServerResponse
{
    /**
     * @param int $statusCode
     * @param array<string, string> $headers
     * @param string $body
     */
    public function __construct(
        public int    $statusCode,
        public array  $headers,
        public string $body
    ) {
    }

    /**
     * Send response headers and body to standard output
     */
    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->body;
    }
}
