<?php

namespace Paulo\CustomCart\Controller\Cart;

use Magento\Checkout\Controller\Cart\UpdateItemQty as MagentoUpdateItemQty;
use Magento\Checkout\Model\Cart\RequestQuantityProcessor;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote\Item;
use Psr\Log\LoggerInterface;
use Paulo\CustomCart\Helper\Data;

class UpdateItemQty extends MagentoUpdateItemQty
{
    /**
     * UpdateItemQty constructor
     *
     * @param Context $context
     * @param RequestQuantityProcessor $quantityProcessor
     * @param FormKeyValidator $formKeyValidator
     * @param CheckoutSession $checkoutSession
     * @param Json $json
     * @param LoggerInterface $logger
     * @param Data $config
     */
    public function __construct(
        protected Context $context,
        protected RequestQuantityProcessor $quantityProcessor,
        protected FormKeyValidator $formKeyValidator,
        protected CheckoutSession $checkoutSession,
        protected Json $json,
        protected LoggerInterface $logger,
        protected Data $config
    ) {
        parent::__construct(
            $context,
            $quantityProcessor,
            $formKeyValidator,
            $checkoutSession,
            $json,
            $logger
        );
    }

    /**
     * Controller execute method
     *
     * @return void
     */
    public function execute(): void
    {
        if (!$this->config->getEnable()) {
            parent::execute();
        }

        try {
            $this->validateRequest();
            $this->validateFormKey();

            $cartData = $this->getRequest()->getParam('cart');

            $this->validateCartData($cartData);

            $cartData = $this->quantityProcessor->process($cartData);
            $quote = $this->checkoutSession->getQuote();

            $response = [];
            foreach ($cartData as $itemId => $itemInfo) {
                $item = $quote->getItemById($itemId);
                $qty = isset($itemInfo['qty']) ? (double) $itemInfo['qty'] : 0;
                if ($item) {
                    try {
                        $this->updateItemQuantity($item, $qty);
                    } catch (LocalizedException $e) {
                        $this->checkoutSession->setMessageAjaxUpdateDelete(__($e->getMessage()));
                        $response[] = [
                            'error' => $e->getMessage(),
                            'itemId' => $itemId
                        ];
                    }
                }
            }
            $this->jsonResponse(count($response)? json_encode($response) : '');
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->checkoutSession->setMessageAjaxUpdateDelete(__($e->getMessage()));
            $this->jsonResponse('Something went wrong while saving the page. Please refresh the page and try again.');
        }
    }

    /**
     * Updates quote item quantity.
     *
     * @param Item $item
     * @param float $qty
     * @return void
     * @throws LocalizedException
     */
    private function updateItemQuantity(Item $item, float $qty)
    {
        if ($qty > 0) {
            $item->clearMessage();
            $item->setHasError(false);
            $item->setQty($qty);

            if ($item->getHasError()) {
                throw new LocalizedException(__($item->getMessage()));
            }
        }
    }

    /**
     * JSON response builder.
     *
     * @param string $error
     * @return void
     */
    private function jsonResponse(string $error = '')
    {
        $this->getResponse()->representJson(
            $this->json->serialize($this->getResponseData($error))
        );
    }

    /**
     * Returns response data.
     *
     * @param string $error
     * @return array
     */
    private function getResponseData(string $error = ''): array
    {
        $response = ['success' => true];

        if (!empty($error)) {
            $response = [
                'success' => false,
                'error_message' => $error,
            ];
        }

        return $response;
    }

    /**
     * Validates the Request HTTP method
     *
     * @return void
     * @throws NotFoundException
     */
    private function validateRequest()
    {
        if ($this->getRequest()->isPost() === false) {
            throw new NotFoundException(__('Page Not Found'));
        }
    }

    /**
     * Validates form key
     *
     * @return void
     * @throws LocalizedException
     */
    private function validateFormKey()
    {
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            throw new LocalizedException(
                __('Something went wrong while saving the page. Please refresh the page and try again.')
            );
        }
    }

    /**
     * Validates cart data
     *
     * @param array|null $cartData
     * @return void
     * @throws LocalizedException
     */
    private function validateCartData($cartData = null)
    {
        if (!is_array($cartData)) {
            throw new LocalizedException(
                __('Something went wrong while saving the page. Please refresh the page and try again.')
            );
        }
    }
}
