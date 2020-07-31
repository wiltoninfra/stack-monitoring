<?php

namespace Promo\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Output\ConsoleOutput;

class EnsureIndexesCommand extends Command
{
    protected $signature = 'ensure-indexes';

    protected $description = 'Assegura que os índices definidos nos documentos e sub-documentos são criados.';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Executa o comando
     *
     * @return mixed
     */
    public function handle()
    {
        $output = new ConsoleOutput();

        $output ->writeLn('Assegurando criação dos índices...');
        // Tratando dos índices do Mongo
        \PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager::getSchemaManager()->ensureIndexes();

        $success_message = 'Índices do banco atualizados';

        \Log::info($success_message);
        $output->writeLn($success_message);
    }
}