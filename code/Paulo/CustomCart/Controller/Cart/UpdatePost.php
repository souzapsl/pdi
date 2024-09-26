<?php

namespace Paulo\CustomCart\Controller\Cart;

use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Cart\RequestQuantityProcessor;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Store\Model\StoreManagerInterface;
use Paulo\CustomCart\Helper\Data;

class UpdatePost extends \Magento\Checkout\Controller\Cart\UpdatePost
{
    /**
     * @param Data $config
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param Validator $formKeyValidator
     * @param Cart $cart
     * @param JsonFactory $jsonFactory
     * @param PriceHelper $priceHelper
     * @param RequestQuantityProcessor $quantityProcessor
     */
    public function __construct(
        protected Data $config,
        protected Context $context,
        protected ScopeConfigInterface $scopeConfig,
        protected Session $checkoutSession,
        protected StoreManagerInterface $storeManager,
        protected Validator $formKeyValidator,
        Cart $cart,
        private readonly JsonFactory $jsonFactory,
        private readonly PriceHelper $priceHelper,
        protected RequestQuantityProcessor $quantityProcessor
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart,
            $quantityProcessor
        );
    }

    /**
     * Empty customer's shopping cart
     *
     * @return void
     */
    protected function _emptyShoppingCart(): void
    {
        try {
            $this->cart->truncate()->save();
        } catch (\Magento\Framework\Exception\LocalizedException $exception) {
            $this->checkoutSession->setMessageAjaxUpdateDelete(__($exception->getMessage()));
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Exception $exception) {
            $this->checkoutSession->setMessageAjaxUpdateDelete(__('We can\'t update the shopping cart.'));
            $this->messageManager->addExceptionMessage($exception, __('We can\'t update the shopping cart.'));
        }
    }

    /**
     * Update customer's shopping cart
     *
     * @return void
     */
    protected function _updateShoppingCart(): void
    {
        try {
            $cartData = $this->getRequest()->getParam('cart');
            if (is_array($cartData)) {
                if (!$this->cart->getCustomerSession()->getCustomerId() && $this->cart->getQuote()->getCustomerId()) {
                    $this->cart->getQuote()->setCustomerId((int)null);
                }
                $cartData = $this->quantityProcessor->process($cartData);
                $cartData = $this->cart->suggestItemsQty($cartData);
                $this->cart->updateItems($cartData)->save();
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->checkoutSession->setMessageAjaxUpdateDelete(__($e->getMessage()));
            $this->messageManager->addErrorMessage(
                $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($e->getMessage())
            );
        } catch (\Exception $e) {
            $this->checkoutSession->setMessageAjaxUpdateDelete(__('We can\'t update the shopping cart.'));
            $this->messageManager->addExceptionMessage($e, __('We can\'t update the shopping cart.'));
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
        }
    }

    /**
     * Update shopping cart data action
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function execute(): mixed
    {
        if (!$this->config->getEnable()) {
            return parent::execute();
        }

        $this->checkoutSession->setMessageAjaxUpdateDelete('');

        $url = $this->_objectManager->create(\Magento\Framework\UrlInterface::class)->getUrl('*/*');

        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->checkoutSession->setMessageAjaxUpdateDelete(__('We can\'t update the shopping cart.'));
            $info = ['success' => false, 'subtotal' => '', 'items' => [], 'url' => $url];
            return $this->jsonFactory->create()->setData($info);
        }

        $updateAction = (string)$this->getRequest()->getParam('update_cart_action');

        switch ($updateAction) {
            case 'empty_cart':
                $this->_emptyShoppingCart();
                break;
            case 'update_qty':
                $this->_updateShoppingCart();
                break;
            default:
                $this->_updateShoppingCart();
        }

        if($updateAction !== 'empty_cart') {
            $info = [];

            $quote = $this->cart->getQuote();

            $info['success'] = true;

            foreach ($quote->getAllItems() as $item) {
                $info['items'][] = [
                    'id' => $item->getItemId(),
                    'subtotal' => $this->priceHelper->currency(
                        $item->getRowTotal(),
                        true,
                        false
                    ),
                    'price' => $this->priceHelper->currency(
                        $item->getCalculationPrice(),
                        true,
                        false
                    )
                ];
            }
            return $this->jsonFactory->create()->setData($info);
        }

        return $this->_goBack();
    }
}
