<?php

namespace App\Providers;

use Anthropic\Client;
use App\Services\ClaudeService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ClaudeService::class, function () {
            $client = new Client(
                apiKey: (string) config('services.anthropic.api_key'),
                // SDK сам ретраїть 429/529/5xx з backoff; 120с вистачає на
                // генерацію рецепта, не тримаючи queue worker 10 хвилин (дефолт SDK).
                requestOptions: ['timeout' => 120, 'maxRetries' => 2],
            );

            return new ClaudeService(
                $client,
                (string) config('services.anthropic.default_model'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS URL generation when the app is served over TLS (driven by
        // APP_URL), so assets/links aren't emitted as http:// behind the proxy.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
