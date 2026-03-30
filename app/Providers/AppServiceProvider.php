<?php

// FILE: app/Providers/AppServiceProvider.php | V4

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(App::getLocale());

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');

            return Limit::perMinute(5)->by($email.$request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        Paginator::defaultView('vendor.pagination.default');
        Paginator::defaultSimpleView('vendor.pagination.simple-default');

        View::share('appFooterVersionLabel', $this->resolveAppFooterVersionLabel());
    }

    protected function resolveAppFooterVersionLabel(): string
    {
        if (! App::environment('local')) {
            return '';
        }

        return Cache::remember('app_footer_version_label', now()->addSeconds(15), function () {
            $shortHash = $this->resolveGitShortHashFromFiles();

            if ($shortHash === null || $shortHash === '') {
                return 'dev-unknown';
            }

            return 'dev-'.$shortHash;
        });
    }

    protected function resolveGitShortHashFromFiles(): ?string
    {
        $gitPath = base_path('.git');

        if (is_file($gitPath)) {
            $gitFileContent = @file_get_contents($gitPath);

            if (! is_string($gitFileContent) || trim($gitFileContent) === '') {
                return null;
            }

            if (preg_match('/gitdir:\s*(.+)/i', trim($gitFileContent), $matches)) {
                $gitPath = base_path(trim($matches[1]));
            }
        }

        if (! is_dir($gitPath)) {
            return null;
        }

        $headPath = $gitPath.DIRECTORY_SEPARATOR.'HEAD';

        if (! is_file($headPath)) {
            return null;
        }

        $headContent = @file_get_contents($headPath);

        if (! is_string($headContent)) {
            return null;
        }

        $headContent = trim($headContent);

        if ($headContent === '') {
            return null;
        }

        if (str_starts_with($headContent, 'ref: ')) {
            $ref = trim(substr($headContent, 5));
            $refPath = $gitPath.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $ref);

            if (is_file($refPath)) {
                $hash = @file_get_contents($refPath);

                if (is_string($hash) && trim($hash) !== '') {
                    return substr(trim($hash), 0, 7);
                }
            }

            $packedRefHash = $this->resolvePackedRefHash($gitPath, $ref);

            if ($packedRefHash !== null) {
                return substr($packedRefHash, 0, 7);
            }

            return null;
        }

        return substr($headContent, 0, 7);
    }

    protected function resolvePackedRefHash(string $gitPath, string $ref): ?string
    {
        $packedRefsPath = $gitPath.DIRECTORY_SEPARATOR.'packed-refs';

        if (! is_file($packedRefsPath)) {
            return null;
        }

        $lines = @file($packedRefsPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (! is_array($lines)) {
            return null;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, '^')) {
                continue;
            }

            [$hash, $packedRef] = array_pad(preg_split('/\s+/', $line, 2), 2, null);

            if ($packedRef === $ref && ! empty($hash)) {
                return trim($hash);
            }
        }

        return null;
    }
}
