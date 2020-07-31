<?php

namespace Promo\Console\Commands;

use Illuminate\Console\Command;
use PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager;
use Promo\Documents\Campaign;
use Promo\Events\Campaign\CampaignChangeEvent;
use Promo\Services\CampaignVersionService;

class GenerateVersionCampaign extends Command
{
    protected $signature = 'promo:generate-campaignVersion';

    protected $description = 'generate campaign version all campaigns activeted.';

    protected $campaignVersionService;

    public function __construct()
    {
        $this->campaignVersionService = app()->make(CampaignVersionService::class);
        parent::__construct();
    }

    public function handle()
    {
        $criteria = [
            'active' => true,
            'communication' => false,
        ];

        $this->info('Iniciando ...');
        try {
            $repo = DocumentManager::getRepository(Campaign::class);
            $mongoCursor = $repo->findBy($criteria);
            $campaigns = collect($mongoCursor);
            $this->info("Encontrada {$campaigns->count()} campanhas para criar versões.");
            foreach ($campaigns as $campaign) {
                $this->info("Criando versão para a campanha: {$campaign->getName()} - {$campaign->getId()}");
                event(new CampaignChangeEvent($campaign->getId()));
            }
            DocumentManager::flush();
        } catch (\Exception $e) {
            $this->info('ERRO: ');
            $this->info($e->getMessage());
        }
        $this->info('OK');

    }
}
