<?php

namespace Paulo\CustomCart\Helper;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class AjaxUpdateDelete extends Template
{
    /**
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param array $data
     */
    public function __construct(
        Context $context,
        protected CheckoutSession $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function verifyMessageAjaxUpdateDelete()
    {
        if ($messageSession = $this->checkoutSession->getMessageAjaxUpdateDelete()) {
            $this->checkoutSession->setMessageAjaxUpdateDelete('');
            return $messageSession;
        }
        return null;
    }
}
