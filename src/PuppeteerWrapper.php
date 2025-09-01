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
    private function processRequest(string $action, string $html, array $options, string $method = Request::METHOD_POST): string
    {
        $footerAlign = $options['footer-centered'] ?? false ? 'center' : 'right';
        $footerContent = str_replace(
            [
                '[page]',
                '[nbPages]',
            ],
            [
                '<span class="pageNumber"></span>',
                '<span class="totalPages"></span>',
            ],
            (string) ($options['footer-content'] ?? '[page] / [nbPages]')
        );

        $footerTemplate = '<div style="font-size: 10px; text-align: ' . $footerAlign . ';' . ('right' === $footerAlign ? ' margin-right: 25px;' : '') . ' width: 100%;">' . $footerContent . '</div>';

        $defaultOptions = [
            'displayHeaderFooter' => false,
            'headerTemplate' => '<div></div>', // no header
            'footerTemplate' => $footerTemplate,

            'landscape' => false,
            'scale' => 1,
            'format' => 'A4',
            'margin' => [
                'top' => '1cm',
                'right' => '1cm',
                'bottom' => '1cm',
                'left' => '1cm',
            ],
            'printBackground' => true,
            // 'tagged' => true, // @experimental
        ];

        $uri = $this->puppeteerUrl . $action;

        $body = json_encode([
            'html' => $html,
            'options' => array_merge($defaultOptions, $options),
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

    /** @param array<mixed> $options */
    public function pdf(string $html, array $options = []): string
    {
        return $this->processRequest('/pdf', $html, $options);
    }

    public function screenshot(string $html, array $options = []): string
    {
        return $this->processRequest('/screenshot', $html, array_merge(
            [
                'format' => 'jpeg',
                'quality' => 80,
            ],
            $options,
        ));
    }
}
