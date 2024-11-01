<?php

namespace Paulo\AjaxCompare\Plugin\Controller\Product\Compare;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Controller\Product\Compare\Remove as CompareRemove;
use Magento\Catalog\Helper\Product\Compare;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Compare\Item;
use Magento\Catalog\Model\Product\Compare\ItemFactory;
use Magento\Catalog\Model\Product\Compare\ListCompare;
use Magento\Catalog\Model\ResourceModel\Product\Compare\Item\CollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Visitor;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Paulo\AjaxCompare\Helper\Data as Config;

class Remove extends CompareRemove
{
    /**
     * @param Config $config
     * @param Context $context
     * @param ItemFactory $compareItemFactory
     * @param CollectionFactory $itemCollectionFactory
     * @param Session $customerSession
     * @param Visitor $customerVisitor
     * @param ListCompare $catalogProductCompareList
     * @param \Magento\Catalog\Model\Session $catalogSession
     * @param StoreManagerInterface $storeManager
     * @param Validator $formKeyValidator
     * @param PageFactory $resultPageFactory
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        protected Config $config,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Model\Product\Compare\ItemFactory $compareItemFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Compare\Item\CollectionFactory $itemCollectionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\Visitor $customerVisitor,
        \Magento\Catalog\Model\Product\Compare\ListCompare $catalogProductCompareList,
        \Magento\Catalog\Model\Session $catalogSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        Validator $formKeyValidator,
        PageFactory $resultPageFactory,
        ProductRepositoryInterface $productRepository
    ) {
        parent::__construct(
            $context,
            $compareItemFactory,
            $itemCollectionFactory,
            $customerSession,
            $customerVisitor,
            $catalogProductCompareList,
            $catalogSession,
            $storeManager,
            $formKeyValidator,
            $resultPageFactory,
            $productRepository
        );
    }

    /**
     * Remove item from compare list.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @return ResultInterface
     * @throws NoSuchEntityException
     */
    public function execute(): ResultInterface
    {
        if (!$this->config->getConfig('general/enabled')) {
            return parent::execute();
        }

        $productId = (int)$this->getRequest()->getParam('product');

        /** @var $helper Compare */
        $helper = $this->_objectManager->get(Compare::class);

        if ($this->_formKeyValidator->validate($this->getRequest()) && $productId) {

            $this->_view->loadLayout();

            $storeId = $this->_storeManager->getStore()->getId();
            try {
                /** @var Product $product */
                $product = $this->productRepository->getById($productId, false, $storeId);
            } catch (NoSuchEntityException $e) {
                $product = null;
            }

            if ($product && $product->getStatus() !== Status::STATUS_DISABLED) {
                /** @var $item Item */
                $item = $this->_compareItemFactory->create();
                if ($this->_customerSession->isLoggedIn()) {
                    $item->setCustomerId($this->_customerSession->getCustomerId());
                } elseif ($this->_customerId) {
                    $item->setCustomerId($this->_customerId);
                } else {
                    $item->addVisitorId($this->_customerVisitor->getId());
                }

                $item->loadByProduct($product);
                if ($item->getId()) {
                    $item->delete();

                    $popup = $this->_view->getLayout()
                        ->createBlock('Magento\Catalog\Block\Product\Compare\ListCompare')
                        ->setTemplate('Paulo_AjaxCompare::popup.phtml')
                        ->toHtml();

                    $productName = $this->_objectManager->get(Escaper::class)->escapeHtml($product->getName());
                    $this->_eventManager->dispatch('catalog_product_compare_remove_product', ['product' => $item]);
                    $response['success'] = true;
                    $response['popup'] = $popup;
                    $response['message_type'] = 'success';
                    $response['message'] = __('You removed product %1 from the comparison list.', $productName);
                } else {
                    $response['success'] = false;
                    $response['message_type'] = 'error';
                    $response['message'] = __('Unable to remove product from comparison list. 1');
                }
            } else {
                $response['success'] = false;
                $response['message_type'] = 'error';
                $response['message'] = __('Unable to remove product from comparison list. 2');
            }

            $helper->calculate();
            $response['itemsCount'] = $helper->getItemCount();
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $resultJson->setData($response);
            return $resultJson;
        }

        $response['success'] = false;
        $response['message_type'] = 'error';
        $response['message'] = __('Unable to remove product from comparison list. 3');

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($response);
        $helper->calculate();
        return $resultJson;
    }
}
