<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\AppleOAuthConfiguration;
use Illuminate\Console\Command;

class CheckAppleOAuthConfiguration extends Command
{
    protected $signature = 'oauth:apple:check
                            {--require-enabled : Fail when the production Apple OAuth feature flag is disabled}';

    protected $description = 'Validate Sign in with Apple server configuration without printing credentials';

    public function handle(AppleOAuthConfiguration $configuration): int
    {
        $issues = $configuration->issues();
        $enabled = Setting::getVal('openapi_auth_oauth_apple_status', '0') === '1';

        $this->components->twoColumnDetail(
            'Native Bundle ID',
            $configuration->nativeClientId() !== '' ? '<fg=green>configured</>' : '<fg=red>missing</>'
        );
        $this->components->twoColumnDetail(
            'Accepted audiences',
            $configuration->audiences() !== [] ? '<fg=green>configured</>' : '<fg=red>missing</>'
        );
        $this->components->twoColumnDetail(
            'Team ID / Key ID / EC P-256 private key',
            $issues === [] ? '<fg=green>valid</>' : '<fg=red>invalid or incomplete</>'
        );
        $this->components->twoColumnDetail(
            'Open API feature flag',
            $enabled ? '<fg=green>enabled</>' : '<fg=yellow>disabled</>'
        );

        if ($issues !== []) {
            $this->newLine();
            foreach ($issues as $issue) {
                $this->components->error($issue);
            }

            return self::FAILURE;
        }

        if ($this->option('require-enabled') && ! $enabled) {
            $this->components->error('Apple OAuth đã cấu hình nhưng openapi_auth_oauth_apple_status vẫn đang tắt.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Apple OAuth server configuration is ready. No credential value was printed.');

        return self::SUCCESS;
    }
}
