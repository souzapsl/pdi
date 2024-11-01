<?php
namespace Paulo\AjaxCompare\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Module\Manager;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const string PATH = "paulo_ajaxcompare/";
    /**
     * @param Context $context
     * @param Manager $moduleManager
     */
    public function __construct(Context $context, protected Manager $moduleManager)
    {
        parent::__construct($context);
    }

    public function getConfig($config='')
    {
        if ($config){
            return $this->scopeConfig->getValue(self::PATH . $config, ScopeInterface::SCOPE_STORE);
        }
        return $this->scopeConfig;
    }
}
