<?php
namespace Paulo\CustomCart\Controller\Cart;

use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class Delete extends \Magento\Checkout\Controller\Cart\Delete
{
    /**
     * @param JsonFactory $jsonFactory
     * @param Session $checkoutSession
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Validator $formKeyValidator
     * @param Cart $cart
     */
    public function __construct(
        protected JsonFactory $jsonFactory,
        protected Session $checkoutSession,
        Context $context,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Validator $formKeyValidator,
        Cart $cart,
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );
    }

    /**
     * Delete shopping cart item action
     *
     * @return Redirect|Json
     * @throws NoSuchEntityException
     */
    public function execute(): Redirect|Json
    {
        $this->checkoutSession->setMessageAjaxUpdateDelete('');

        $url = $this->_objectManager->create(\Magento\Framework\UrlInterface::class)->getUrl('*/*');

        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $message = __('We can\'t remove the item.');
            $this->checkoutSession->setMessageAjaxUpdateDelete($message);
            $arrayInfoReturn = ['success' => false, 'itemsCount' => 0, 'url' => $url];
            return $this->jsonFactory->create()->setData($arrayInfoReturn);
        }

        $id = (int)$this->getRequest()->getParam('id');

        if ($id) {
            $item = $this->cart->getQuote()->getItemById($id);
            if ($item) {
                $this->cart->removeItem($id);
                $this->cart->getQuote()->setTotalsCollectedFlag(false);
                $this->cart->save();
            } else {
                $message = __('We can\'t remove the item.');
                $this->checkoutSession->setMessageAjaxUpdateDelete($message);
                $arrayInfoReturn = ['success' => false, 'itemsCount' => 0, 'url' => $url];
                return $this->jsonFactory->create()->setData($arrayInfoReturn);
            }
        } else {
            $message = __('We can\'t remove the item.');
            $this->checkoutSession->setMessageAjaxUpdateDelete($message);
            $arrayInfoReturn = ['success' => false, 'itemsCount' => 0, 'url' => $url];
            return $this->jsonFactory->create()->setData($arrayInfoReturn);
        }

        $quote = $this->cart->getQuote();
        $itemsCount = $quote->getItemsCount();
        $arrayInfoReturn = [
            'success' => true,
            'itemsCount' => $itemsCount,
            'url' => $url
        ];
        return $this->jsonFactory->create()->setData($arrayInfoReturn);
    }
}
