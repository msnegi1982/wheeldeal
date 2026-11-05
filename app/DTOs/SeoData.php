<?php

namespace App\DTOs;

final class SeoData
{
    /**
     * @param  array<int, array{label: string, url: string}>  $breadcrumbs
     * @param  array<int, array<string, mixed>>  $jsonLd
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $canonical,
        public string $robots = 'index,follow',
        public ?string $image = null,
        public string $type = 'website',
        public array $jsonLd = [],
        public array $breadcrumbs = [],
        public ?string $h1 = null,
    ) {}

    public function noindex(): bool
    {
        return str_contains($this->robots, 'noindex');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'canonical' => $this->canonical,
            'robots' => $this->robots,
            'image' => $this->image,
            'type' => $this->type,
            'jsonLd' => $this->jsonLd,
            'breadcrumbs' => $this->breadcrumbs,
            'h1' => $this->h1,
        ];
    }
}
