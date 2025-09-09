<?php

namespace Tapomix\ChromeWrapper;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

abstract class BaseWrapper
{
    /** @var array<string, mixed> */
    protected array $options = [];

    abstract public function pdf(string $html): string;

    abstract public function screenshot(string $html): string;

    public function dwlPdf(string $html, string $fileName): Response
    {
        return $this->download(
            content: $this->pdf($html),
            fileName: $fileName,
            mime: 'application/pdf',
        );
    }

    public function dwlImg(string $html, string $fileName): Response
    {
        return $this->download(
            content: $this->screenshot($html),
            fileName: $fileName,
            mime: 'image/png', // image/jpeg
        );
    }

    public function setScale(float $scale = 1.0): static
    {
        $min = 0.1;
        $max = 5;

        if ($scale <= $min || $scale > $max) {
            throw new \Exception(sprintf('Scale must be in [%.1f, %.1f]', $min, $max));
        }

        $this->options['scale'] = $scale;

        return $this;
    }

    public function setMargins(float $top = 1.0, float $bottom = 1.0, float $left = 1.0, float $right = 1.0): static
    {
        $this->options['margins'] = [
            'top' => $top,
            'bottom' => $bottom,
            'left' => $left,
            'right' => $right,
        ];

        return $this;
    }

    /**
     * @param int<0, max> $width
     * @param int<0, max> $height
     */
    public function setViewport(int $width = 0, int $height = 0): static
    {
        if (0 === $width && 0 === $height) {
            throw new \Exception("Width & Height can't both be zero");
        }

        $this->options['viewport'] = [
            'width' => $width,
            'height' => $height,
        ];

        return $this;
    }

    /** @return array<string, string|bool> */
    protected function buildOptions(): array
    {
        return [
            'printBackground' => true,
            'preferCSSPageSize' => true, // => use @page in css
            'displayHeaderFooter' => (isset($this->options['header']) || isset($this->options['footer'])),
            'headerTemplate' => isset($this->options['header']) ? $this->buildHeaderFooter($this->options['header']) : '<div></div>',
            'footerTemplate' => isset($this->options['footer']) ? $this->buildHeaderFooter($this->options['footer']) : '<div></div>',
        ];
    }

    /** @param array{text: string, align: 'left'|'center'|'right'} $data */
    protected function buildHeaderFooter(array $data): string
    {
        return str_replace(
            [
                '[page]', // current page number
                '[nbPages]', // total pages in document
                '[title]', // document title
                '[url]', // document location (about:blank)
                '[date]', // formatted print date (n/j/y, g:i A)
            ],
            [
                '<span class="pageNumber"></span>',
                '<span class="totalPages"></span>',
                '<span class="title"></span>',
                '<span class="url"></span>',
                '<span class="date"></span>',
            ],
            '<div style="font-size: 10px; text-align: ' . $data['align'] . '; margin-right: 25px; width: 100%;">' . $data['text'] . '</div>'
        );
    }

    public function setHeader(string $text, string $align = 'center'): static
    {
        $this->options['header'] = [
            'text' => $text,
            'align' => $align,
        ];

        return $this;
    }

    public function setFooter(string $text, string $align = 'right'): static
    {
        $this->options['footer'] = [
            'text' => $text,
            'align' => $align,
        ];

        return $this;
    }

    public function setPageNumber(string $text = '[page] / [nbPages]', string $align = 'right'): static
    {
        return $this->setFooter($text, $align);
    }

    /** @param array<mixed> $headers */
    protected function download(
        string $content,
        string $fileName,
        string $mime,
        array $headers = [],
        int $status = Response::HTTP_OK,
    ): Response {
        $response = new Response($content, $status, $headers);

        $response->headers->add([
            'Content-Type' => $mime,
            'Content-Disposition' => $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName),
        ]);

        return $response;
    }
}
