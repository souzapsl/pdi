<?php

namespace Paulo\AjaxCompare\Controller\Compare;

use Magento\Catalog\Helper\Product\Compare;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

class View extends Action
{
    /**
     * @return ResultInterface|void
     */
    public function execute()
    {
        if ($this->getRequest()->isAjax()) {
            $this->_view->loadLayout();

            $popup = $this->_view->getLayout()
                ->createBlock('Magento\Catalog\Block\Product\Compare\ListCompare')
                ->setTemplate('Paulo_AjaxCompare::popup.phtml')
                ->toHtml();
            $response['success'] = true;
            $response['popup'] = $popup;
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $resultJson->setData($response);
            $this->_objectManager->get(Compare::class)->calculate();
            return $resultJson;
        }
    }
}
