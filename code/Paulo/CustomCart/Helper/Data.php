<?php

namespace Paulo\CustomCart\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
    const string ENABLE = 'custom_cart/general/enable';

    /**
     * @param Context $context
     */
    public function __construct(Context $context) {
        parent::__construct($context);
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function getEnable($storeId = null): bool
    {
        return $this->scopeConfig->getValue(self::ENABLE, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
