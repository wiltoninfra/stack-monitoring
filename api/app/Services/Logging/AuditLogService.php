<?php

namespace Promo\Services\Logging;

use PicPay\Common\Lumen\Doctrine\ODM\Facades\DocumentManager;
use Promo\Documents\Other\Log;

/**
 * Classe responsável por enviar (abstratamente) logs para auditoria
 *
 * A princípio este serviço está sendo utilizado para logs de acesso a endpoints
 * deste serviço via growthdash
 */
class AuditLogService
{
    /**
     * Função de interface para envio de logs
     *
     * @param array $data
     */
    public function sendLog(array $data): void
    {
        $this->addToDatabase($data);
        $this->sendToExternalLog($data);
    }

    /**
     * Envia logs para o banco do serviço
     *
     * @param array $data
     */
    private function addToDatabase(array $data): void
    {
        $log = new Log($data);

        DocumentManager::persist($log);
        DocumentManager::flush();
    }

    /**
     * Envia logs para serviço externo (Graylog, por agora)
     *
     * @param array $data
     */
    private function sendToExternalLog(array $data): void
    {
        \Log::info('Log de acesso', array_merge($data, ['access_log' => true]));
    }
}
