<?php

namespace Promo\Documents;

use Promo\Documents\Embeded\CouponStats;
use Promo\Documents\Embeded\CouponConditions;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Promo\Documents\Enums\CouponRedirectionType;
use Promo\Exceptions\ValidationException;

/** 
 * @ODM\Document(collection="coupons", repositoryClass="Promo\Repositories\CouponRepository")
 * @ODM\UniqueIndex(keys={"code"="asc", "campaign"="asc"})
 * @ODM\HasLifecycleCallbacks
 */
class Coupon extends BaseDocument
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="string") */
    protected $code;

    /** @ODM\Field(type="boolean") */
    protected $active;

    /**
     * @ODM\Field(type="string")
     * @ODM\Index
     */
    protected $redirection_type;

    /** @ODM\Field(type="integer") */
    protected $max_associations;

    /** @ODM\Field(type="boolean") */
    protected $global;

    /** @ODM\Field(type="string") */
    protected $webview_url;

    /** @ODM\Field(type="string") */
    protected $app_screen_path;

    /** @ODM\ReferenceOne(targetDocument="Promo\Documents\Campaign", storeAs="id") */
    protected $campaign;

    /** @ODM\EmbedOne(targetDocument="Promo\Documents\Embeded\CouponStats") */
    protected $stats;

    /** @ODM\EmbedOne(targetDocument="Promo\Documents\Embeded\CouponConditions") */
    protected $conditions;

    public function __construct(?Campaign $campaign, string $code)
    {
        $this->campaign = $campaign;
        // Garante que todos os cupons tenham todas maiúsculas
        $this->code = strtoupper($code);
        $this->active = true;
        $this->stats = new CouponStats();
        $this->conditions = new CouponConditions();
    }

    /**
     * Obtém o id
     * 
     * @return string
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Obtém o código do cupom
     * 
     * @return string
     */ 
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Número máximo de associações
     * 
     * @return int|null
     */ 
    public function getMaxAssociations()
    {
        return $this->max_associations;
    }

    /**
     * Altera o número máximo de associações
     *
     * @param int $max_associations
     * @return self
     */ 
    public function setMaxAssociations($max_associations)
    {
        $this->max_associations = $max_associations;

        return $this;
    }

    /**
     * Obtém o valor de active
     */ 
    public function isActive()
    {
        return $this->active && $this->isValid();
    }

    /**
     * Ativa ou desativa cupom
     *
     * @param boolean $active
     * @return self
     */ 
    public function setActive(bool $active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Ativa cupom
     */
    public function enable()
    {
        $this->active = true;
    }

    /**
     * Desativa cupom
     */
    public function disable()
    {
        $this->active = false;
    }

    /**
     * Obtém a campanha
     * 
     * @return Campaign|null
     */ 
    public function getCampaign(): ?Campaign
    {
        return $this->campaign;
    }

    /**
     * Obtém documento de estatísticas
     */ 
    public function getStats(): CouponStats
    {
        return $this->stats;
    }

    /**
     * Obtém documento de condições de um cupom
     */
    public function getConditions(): ?CouponConditions
    {
        return $this->conditions;
    }

    /**
     * Verifica se o cupon é ainda válido
     *
     * @return boolean
     */
    public function isValid(): bool
    {
        // Verifica se o cupom já alcançou o limite de usos (se definido) ou se a campanha dele já expirou
        if ( ($this->max_associations !== null && $this->stats->getCurrentUses() >= $this->max_associations)
             || (isset($this->campaign) && $this->campaign->isActive() === false))
        {
            $this->disable();
            return false;
        }

        return true;
    }

    /**
     * Se cupom é global ou associado
     */ 
    public function isGlobal()
    {
        return $this->global;
    }

    /**
     * Altera o valor de global
     *
     * @param bool $global
     * @return self
     */ 
    public function setGlobal(bool $global)
    {
        $this->global = $global;

        return $this;
    }

    /**
     * Obém o endereço do webview de final de aplicação de cupom
     *
     * @return mixed
     */
    public function getWebviewUrl()
    {
        return $this->webview_url;
    }

    /**
     * Altera o endereço de webview
     *
     * @param mixed $webview_url
     * @return $this
     */
    public function setWebviewUrl($webview_url)
    {
        $this->webview_url = $webview_url;

        return $this;
    }

    /**
     * Obém o caminho de retorno para a tela do aplicativo
     *
     * @return mixed
     */
    public function getAppScreenPath()
    {
        return $this->app_screen_path;
    }

    /**
     * Altera o caminho de retorno do aplicativo
     *
     * @param mixed $app_screen_path
     * @return $this
     */
    public function setAppScreenPath($app_screen_path)
    {
        $this->app_screen_path = $app_screen_path;

        return $this;
    }

    /**
     * Obtém o valor de redirection_type
     *
     * @return string
     */
    public function getRedirectionType()
    {
        return $this->redirection_type;
    }

    /**
     * Altera o valor de redirection_type
     *
     * @param string $redirection_type
     * @return self
     * @throws ValidationException
     */
    public function setRedirectionType(string $redirection_type)
    {
        if (!in_array($redirection_type, CouponRedirectionType::getFields()))
        {
            throw new ValidationException('Tipo inesperado de redirect.');
        }

        $this->redirection_type = $redirection_type;

        return $this;
    }

    /**
     * @ODM\PostLoad
     */
    public function postLoad()
    {
        if ($this->redirection_type === null)
        {
            $this->redirection_type = CouponRedirectionType::WEBVIEW;
        }
    }
}