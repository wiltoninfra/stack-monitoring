<?php

namespace Promo\Documents\Embeded;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Mockery\Exception;
use Promo\Documents\Campaign;
use Promo\Services\CampaignService;

/**
 * @ODM\EmbeddedDocument
 *
 */
class NotificationVariantPayload
{

    /** @ODM\Field(type="string") */
    protected $name;

    /** @ODM\Field(type="float") */
    protected $percentage;

    /**
     * @ODM\EmbedOne(targetDocument="Promo\Documents\Embeded\Target")
     * @var \Promo\Documents\Embeded\Target
     */
    protected $target;

    /**
     * @ODM\EmbedOne(targetDocument="Promo\Documents\Embeded\Push")
     * @var \Promo\Documents\Embeded\Push
     */
    protected $push;

    /**
     * @ODM\EmbedOne(targetDocument="Promo\Documents\Embeded\In_App")
     * @var \Promo\Documents\Embeded\In_App
     */
    protected $in_app;

    /**
     * @ODM\EmbedOne(targetDocument="Promo\Documents\Embeded\Sms")
     * @var \Promo\Documents\Embeded\Sms
     */
    protected $sms;

    /**
     * Obtém o valor de name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Altera o valor do name
     *
     * @param string $name
     * @return  self
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Obtém o valor de percentage
     */
    public function getPercentage()
    {
        return $this->percentage;
    }

    /**
     * Altera o valor do percentage
     *
     * @param float $percentage
     * @return  self
     */
    public function setPercentage(float $percentage)
    {
        $this->percentage = $percentage;

        return $this;
    }

    public function getPush()
    {
        return $this->push ?? null;
    }

    public function getInApp()
    {
        return $this->in_app ?? null;
    }

    public function getSMS()
    {
        return $this->sms ?? null;
    }

    public function getTarget()
    {
        return $this->target ?? null;
    }
    /**
     * Altera o valor do NotificationVariantPayload
     *
     * @param array $data
     * @return  self
     */
    public function setVariants(?array $data)
    {
        $this->setName($data['name']);
        $this->setPercentage($data['percentage']);

        $this->target = new Target();
        $this->target->setModel($data['target']['model']);

        if (array_key_exists('href', $data['target']) === true)
        {
            $this->target->setHref($data['target']['href']);
        }

        if (array_key_exists('params', $data['target']) === true)
        {
            $this->target->setParams($data['target']['params']);
        }

        if (array_key_exists('user_properties', $data['target']) === true)
        {
            $this->target->setUserProperties($data['target']['user_properties']);
        }

        if (array_key_exists('mixpanel_properties', $data['target']) === true)
        {
            $this->target->setMixpanelProperties($data['target']['mixpanel_properties']);
        }

        if (array_key_exists('push', $data) === true) {
            $this->push = new Push();

                $this->push->setTitleTemplate($data['push']['title_template']);
                $this->push->setMessageTemplate($data['push']['message_template']);
        }

        if (array_key_exists('in_app', $data) === true) {
            $this->in_app = new In_App();
            $this->in_app->setMessageTemplate($data['in_app']['message_template']);
        }

        if (array_key_exists('sms', $data) === true) {
            $this->sms = new Sms();
            $this->sms->setMessageTemplate($data['sms']['message_template']);
        }

        return $this;
    }

    /**
     * Obtém o valor de NotificationVariantPayload
     */
    public function getVariants(): ?array
    {
        $result = [
            'name' => $this->getName(),
            'percentage'=> $this->getPercentage(),
            'target'=> [
                'model' => $this->target->getModel(),
                'href' => $this->target->getHref(),
                'params' => $this->target->getParams(),
                'user_properties' => $this->target->getUserProperties(),
                'mixpanel_properties' => $this->target->getMixpanelProperties()
            ]
        ];

        if ($this->push !== null)
        {
            $result['push']['title'] = $this->push->getTitle();
            $result['push']['message'] = $this->push->getMessage();
            $result['push']['message_template'] = $this->push->getMessageTemplate();
            $result['push']['title_template'] = $this->push->getTitleTemplate();
        }

        if ($this->in_app !== null)
        {

            $result['in_app']['message'] = $this->in_app->getMessage();
            $result['in_app']['message_template'] = $this->in_app->getMessageTemplate();
        }

        if ($this->sms !== null)
        {
            $result['sms']['message'] = $this->sms->getMessage();
            $result['sms']['message_template'] = $this->sms->getMessageTemplate();
        }


        return $result;
    }

    /**
     * @param Campaign $campaign
     */
    public function translator(Campaign $campaign)
    {
        $campaignService = \app(CampaignService::class); // @todo Refatorar essa linha
        $notificationData = $campaignService->notificationTemplateRuler('', $campaign);

        $this->translatorTemplate($campaign, $notificationData, $this->push)
             ->translatorTemplate($campaign, $notificationData, $this->in_app)
             ->translatorTemplate($campaign, $notificationData, $this->sms);
    }

    /**
     * @param Campaign $campaign
     * @param $notificationData
     * @param $attribute
     */
    private function translatorTemplate(Campaign $campaign, $notificationData, $attribute)
    {
        if(empty($attribute)){
            return $this;
        }

        $template = $attribute->getMessageTemplate();
        if(empty($attribute->getMessageTemplate())){

            if(!empty($attribute->getMessage())){
                $attribute->setMessageTemplate($attribute->getMessage());
            }
        }

        preg_match_all('%{{campaign.(.*?)}}%i', $template, $methods, PREG_PATTERN_ORDER);
        $notificationData = collect($notificationData);

        $notificationDataValues = $notificationData->values()->map(function ($value, $key){
            if(is_array($value)){
                return implode($value, ',');
            }
            return $value;
        })->toArray();

        $notificationDataKeys = $notificationData->keys()->map(function ($value, $key){
            return sprintf("%s%s%s", "{{", $value,"}}");
        })->toArray();

        if($attribute instanceof Push){
            $templateTitle = $attribute->getTitleTemplate();
            $templateTitle = str_replace($notificationDataKeys, $notificationDataValues, $templateTitle);

            if(empty($attribute->getTitleTemplate())){
                if(!empty($attribute->getTitle())){
                    $attribute->setTitleTemplate($attribute->getTitle());
                }
            }

            if(!empty($templateTitle)) {
                $attribute->setTitle($templateTitle);
            }
        }

        $template = str_replace($notificationDataKeys, $notificationDataValues, $template);

        if(!empty($template)){
            $attribute->setMessage($template);
        }

        return $this;
    }

}

/**
 * @ODM\EmbeddedDocument
 */
class Target
{
    /** @ODM\Field(type="string") */
    protected $model;

    /** @ODM\Field(type="string") */
    protected $href;

    /** @ODM\Field(type="string") */
    protected $params;

    /** @ODM\Field(type="string") */
    protected $mixpanel_properties;

    /** @ODM\Field(type="collection") */
    protected $user_properties;

    /**
     * Obtém o valor de model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @return mixed
     */
    public function getMixpanelProperties()
    {
        if ($this->mixpanel_properties == null) {
            $this->setMixpanelProperties('');
        }

        return $this->mixpanel_properties;
    }

    /**
     * @param mixed $mixpanel_properties
     */
    public function setMixpanelProperties($mixpanel_properties)
    {
        $this->mixpanel_properties = $mixpanel_properties;

        return $this;
    }

    /**
     * Altera o valor do model
     *
     * @param string $model
     * @return  self
     */
    public function setModel(string $model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Obtém o valor do href
     */
    public function getHref()
    {
        return $this->href;
    }

    /**
     * Altera o valor do href
     *
     * @param string $href
     * @return  self
     */
    public function setHref(string $href)
    {
        $this->href = $href;

        return $this;
    }

    /**
     * Obtém o valor do params
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Altera o valor do params
     *
     * @param string $params
     * @return  self
     */
    public function setParams(string $params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Obtém o valor do user_properties
     */
    public function getUserProperties()
    {
        return $this->user_properties;
    }

    /**
     * Altera o valor do params
     *
     * @param array $user_properties
     * @return  self
     */
    public function setUserProperties(?array $user_properties)
    {
        $this->user_properties = $user_properties;

        return $this;
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class Push extends templateNotification
{
    /** @ODM\Field(type="string") */
    protected $title;

    /** @ODM\Field(type="string") */
    protected $message;

    /**
     * Obtém o valor de title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Altera o valor do title
     *
     * @param string $title
     * @return  self
     */
    public function setTitle(string $title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Obtém o valor da mensagem
     */
    public function getMessage()
    {
        //aqui vai chamar o render do template
        return $this->message;
    }

    /**
     * Altera o valor do message
     *
     * @param string message
     * @return  self
     */
    public function setMessage(string $message)
    {
        $this->message = $message;

        return $this;
    }

}

/**
 * @ODM\EmbeddedDocument
 */
class In_App extends templateNotification
{
    /** @ODM\Field(type="string") */
    protected $message;

    /**
     * Obtém o valor da message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Altera o valor do message
     *
     * @param string message
     * @return  self
     */
    public function setMessage(string $message)
    {
        $this->message = $message;

        return $this;
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class Sms extends templateNotification
{
    /** @ODM\Field(type="string") */
    protected $message;

    /**
     * Obtém o valor da message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Altera o valor do message
     *
     * @param string message
     * @return  self
     */
    public function setMessage(string $message)
    {
        $this->message = $message;

        return $this;
    }

}

/**
 * Class templateNotification
 * @package Promo\Documents\Embeded
 */
abstract class templateNotification {

    /** @ODM\Field(type="string") */
    protected $title_template;

    /** @ODM\Field(type="string") */
    protected $message_template;

    /**
     * Obtém o valor da message_template
     */
    public function getMessageTemplate()
    {
        return $this->message_template;
    }

    /**
     * Altera o valor do messageTemplate
     *
     * @param string $messageTemplate
     * @return  self
     */
    public function setMessageTemplate(string $messageTemplate)
    {
        $this->message_template = $messageTemplate;

        return $this;
    }

    /**
     * Obtém o valor da title_template
     */
    public function getTitleTemplate()
    {
        return $this->title_template;
    }

    /**
     * Altera o valor do titleTemplate
     *
     * @param string $titleTemplate
     * @return  self
     */
    public function setTitleTemplate(string $titleTemplate)
    {
        $this->title_template = $titleTemplate;

        return $this;
    }
}
