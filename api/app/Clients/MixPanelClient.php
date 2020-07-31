<?php

namespace Promo\Clients;

use Mixpanel;

/**
 * Class MixPanelClient
 * @package App\Clients
 */
class MixPanelClient
{
    /**
     * Mix Panel
     *
     * @var MixPanel
     */
    protected $mixPanel;

    /**
     * new instance of MixPanelClient
     */
    public function __construct()
    {
        $this->mixPanel = new MixPanel(config('client.mixpanel_token'));
    }

    /**
     * increment function
     *
     * @param string $userId
     * @param array $values
     * @return void
     */
    public function set(string $userId, array $values): void
    {
        $this->mixPanel->people->set($userId, $values);
        $this->mixPanel->flush();
        return;
    }

    /**
     * @param string $userId
     * @param string $eventName
     * @param array $eventData
     * @return void
     */
    public function track(string $userId, string $eventName,  array $eventData): void
    {
        $this->mixPanel->identify($userId);
        $this->mixPanel->track($eventName, $eventData);
        $this->mixPanel->flush();

        return;
    }
}
