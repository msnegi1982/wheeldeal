<?php

namespace App\Console\Commands;

use App\Services\Seo\SitemapService;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate
                            {--path= : Write the sitemap to a custom path}
                            {--fresh : Rebuild the sitemap cache before writing}';

    protected $description = 'Generate the public XML sitemap';

    public function handle(SitemapService $sitemap): int
    {
        if ($this->option('fresh')) {
            $sitemap->forget();
        }

        $path = (string) ($this->option('path') ?: config('seo.sitemap_path', public_path('sitemap.xml')));
        $path = $this->absolutePath($path);

        $sitemap->writeTo($path);

        $this->info("Sitemap written to {$path}");

        return self::SUCCESS;
    }

    private function absolutePath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            return $path;
        }

        return base_path($path);
    }
}
