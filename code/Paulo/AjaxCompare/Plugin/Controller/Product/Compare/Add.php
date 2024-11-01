<?php
namespace Paulo\AjaxCompare\Plugin\Controller\Product\Compare;

use Magento\Catalog\Helper\Product\Compare;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Compare\ItemFactory;
use Magento\Catalog\Model\ResourceModel\Product\Compare\Item\CollectionFactory;
use Magento\Customer\Model\Visitor;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Model\Product\Compare\Item;
use Magento\Store\Model\StoreManagerInterface;
use Paulo\AjaxCompare\Helper\Data as Config;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\View\Result\PageFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Compare\ListCompare;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Http\Context as HttpContext;

class Add extends \Magento\Catalog\Controller\Product\Compare\Add
{
    /**
     * @param Config $config
     * @param ListCompare $listCompare
     * @param CustomerSession $customerSession
     * @param HttpContext $httpContext
     * @param Context $context
     * @param ItemFactory $compareItemFactory
     * @param CollectionFactory $itemCollectionFactory
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
        protected ListCompare $listCompare,
        protected CustomerSession $customerSession,
        protected HttpContext $httpContext,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Model\Product\Compare\ItemFactory $compareItemFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Compare\Item\CollectionFactory $itemCollectionFactory,
        \Magento\Customer\Model\Visitor $customerVisitor,
        \Magento\Catalog\Model\Product\Compare\ListCompare $catalogProductCompareList,
        \Magento\Catalog\Model\Session $catalogSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        Validator $formKeyValidator,
        PageFactory $resultPageFactory,
        ProductRepositoryInterface $productRepository,
    )
    {
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
     * @param \Magento\Catalog\Controller\Product\Compare\Add $subject
     * @param $result
     * @return ResultInterface
     * @throws NoSuchEntityException
     * @throws \Exception
     */
    public function afterExecute(\Magento\Catalog\Controller\Product\Compare\Add $subject, $result): ResultInterface
    {
        if ($subject->getRequest()->isAjax()) {

            $this->_view->loadLayout();

            $productId = null;
            if ($this->getRequest()->getParam('product')) {
                $productId = (int)$this->getRequest()->getParam('product');
            }

            $sku = null;
            if ($this->getRequest()->getParam('sku')) {
                $sku = $this->getRequest()->getParam('sku');
            }

            $product = null;
            $response = [];
            $storeId = $this->_storeManager->getStore()->getId();
            try {
                if ($productId) {
                    /** @var Product $product */
                    $product = $this->productRepository->getById($productId, false, $storeId);
                }

                if ($sku) {
                    /** @var Product $product */
                    $product = $this->productRepository->get($sku, false, $storeId);
                }
            } catch (NoSuchEntityException $e) {
                $product = null;
                $response['success'] = false;
                $response['message'] = $e->getMessage();
                $response['message_type'] = 'error';
                $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
                $resultJson->setData($response);
                return $resultJson;
            }

            if ($product) {

                /** @var $item Item */
                $item = $this->_compareItemFactory->create();
                $item->loadByProduct($product);

                $limitCompare = $this->config->getConfig('general/limit');
                if (($this->getCompareItemCount() < $limitCompare) && !$item->getId()) {
                    $this->_catalogProductCompareList->addProduct($product);
                    $this->_eventManager->dispatch('catalog_product_compare_add_product', ['product' => $product]);
                    $response['success'] = true;
                    $response['message_type'] = 'success';
                    $response['message'] = __(
                        "The product %1 has been added to your comparison list.",
                        $product->getName()
                    );
                } else {
                    $response['success'] = true;
                    if ($item->getId()) {
                        $response['message_type'] = 'notice';
                        $response['message'] = __(
                            "This product %1 is already in the comparison list.",
                            $product->getName()
                        );
                    } else {
                        $response['message_type'] = 'error';
                        $response['message'] = __(
                            "Product %1 cannot be added to the comparison list, limit of %2 products reached.",
                            $product->getName(),
                            $limitCompare
                        );
                    }
                }

                $popup = $this->_view->getLayout()
                    ->createBlock('Magento\Catalog\Block\Product\Compare\ListCompare')
                    ->setTemplate('Paulo_AjaxCompare::popup.phtml')
                    ->toHtml();
                $response['popup']   = $popup;
                $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
                $resultJson->setData($response);
                $this->_objectManager->get(Compare::class)->calculate();
                return $resultJson;
            }
        }
        return $result;
    }

    /**
     * @return int
     */
    public function getCompareItemCount(): int
    {
        $isLoggedIn = $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
        if ($isLoggedIn) {
            $customerId = $this->customerSession->getCustomerId();
            $compareItems = $this->listCompare->getItemCollection()->setCustomerId($customerId);
        } else {
            $compareItems = $this->listCompare->getItemCollection()->setVisitorId($this->_customerVisitor->getId());
        }
        return $compareItems->getSize();
    }
}
