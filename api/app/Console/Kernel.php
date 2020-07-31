<?php

namespace Promo\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \Promo\Console\Commands\EnsureIndexesCommand::class,
        \Promo\Console\Commands\TransactionStatusChangeListener::class,
        \Promo\Console\Commands\P2PTransactionStatusChangeListener::class,
        \Promo\Console\Commands\DepositCompletionListener::class,
        \Promo\Console\Commands\GenerateVersionCampaign::class,
        \Promo\Console\Commands\CampaignAssociateListener::class,
        \Promo\Console\Commands\MixpanelMessageSentCommand::class
    ];

    /**
     * Define the application's command schedule.
     * @codeCoverageIgnore
     * @param  Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //
    }
}
