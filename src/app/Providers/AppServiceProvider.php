<?php

namespace App\Providers;

use Anthropic\Client;
use App\Services\ClaudeService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        // Тікет 3.12: 10 генерацій рецептів на годину на юзера (захист від
        // bug-loop / випадкового спаму). Ключ — id юзера (маршрут під auth),
        // IP як захисний фолбек. Дружнє українське 429-повідомлення підхоплює
        // банер помилки з 3.11 (кнопка «Спробувати ще раз»).
        RateLimiter::for('recipe-generation', function (Request $request) {
            return Limit::perHour(10)
                ->by((string) ($request->user()?->id ?: $request->ip()))
                ->response(fn (Request $request, array $headers) => response()->json([
                    'message' => 'Ви досягли ліміту — до 10 рецептів на годину. Спробуйте трохи пізніше.',
                ], 429, $headers));
        });
    }
}
