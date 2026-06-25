<?php

namespace Jguapin\ApprovalMapping\Console;

use Illuminate\Console\Command;

class InstallApprovalMappingCommand extends Command
{
    protected $signature = 'approval-mapping:install {--migrate : Run migrations after publishing} {--with-assets : Publish optional Vue assets}';

    protected $description = 'Install Approval Mapping package assets and migrations.';

    public function handle(): int
    {
        $this->call('vendor:publish', ['--tag' => 'approval-mapping-config', '--force' => true]);
        $this->call('vendor:publish', ['--tag' => 'approval-mapping-migrations', '--force' => true]);
        $this->call('vendor:publish', ['--tag' => 'approval-mapping-views', '--force' => true]);

        if ($this->option('with-assets')) {
            $this->call('vendor:publish', ['--tag' => 'approval-mapping-assets', '--force' => true]);
        }

        if ($this->option('migrate')) {
            $this->call('migrate');
        }

        $this->info('Approval Mapping package installed.');
        $this->line('Next steps:');
        $this->line('- Configure approval-mapping.php if needed.');
        $this->line('- Visit /approval-mapping for Blade UI.');
        $this->line('- Optional: publish assets with --with-assets.');

        return self::SUCCESS;
    }
}
