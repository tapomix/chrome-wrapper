<?php

namespace Tapomix\ChromeWrapper;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PuppeteerWrapper extends BaseWrapper
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly string $puppeteerUrl,
        private readonly string $puppeteerToken,
    ) {
    }

    /** @param array<mixed> $options */
    private function processRequest(string $action, string $html, array $options = [], string $method = Request::METHOD_POST): string
    {
        $uri = $this->puppeteerUrl . $action;

        $body = json_encode([
            'html' => $html,
            'options' => array_merge($this->buildOptions(), $options),
            'viewport' => [
                'width' => $this->options['viewport']['width'] ?? 1920,
                'height' => $this->options['viewport']['height'] ?? 0,
            ],
        ], JSON_THROW_ON_ERROR);

        $request = $this->requestFactory
            ->createRequest($method, $uri)
            ->withHeader('Authorization', 'Bearer ' . $this->puppeteerToken)
            ->withHeader('Content-Type', 'application/json')
        ;

        $request->getBody()->write($body);

        $response = $this->client->sendRequest($request);

        return $this->handleResponse($response);
    }

    private function handleResponse(ResponseInterface $response): string
    {
        $status = $response->getStatusCode();
        $content = (string) $response->getBody();

        if (Response::HTTP_OK !== $status) {
            throw new \RuntimeException(sprintf('Puppeteer service error (%s): %s', $status, $content));
        }

        return $content;
    }

    public function pdf(string $html): string
    {
        return $this->processRequest('/pdf', $html);
    }

    public function screenshot(string $html): string
    {
        return $this->processRequest('/screenshot', $html, [
            'type' => 'png', // jpeg
            // 'quality' => 80, // jpeg only
            'fullPage' => true,
        ]);
    }
}
