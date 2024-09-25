<?php
namespace Paulo\CustomCart\Controller\Cart;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Paulo\CustomCart\Helper\AjaxUpdateDelete;

class ValidateCartMessages extends Action
{
    public function __construct(
        Context $context,
        protected JsonFactory $resultJsonFactory,
        protected AjaxUpdateDelete $ajaxUpdateDelete
    ) {
        parent::__construct($context);
    }

    /**
     * @return Json|ResultInterface|ResponseInterface
     */
    public function execute(): Json|ResultInterface|ResponseInterface
    {
        $messages[] = $this->getAlertMessage();
        $data = ['messages' => $messages];
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData(['data' => $data]);
    }

    /**
     * @return array
     */
    public function getAlertMessage(): array
    {
        $messages = [];
        $messages[] = $this->ajaxUpdateDelete->verifyMessageAjaxUpdateDelete();
        return $messages;
    }
}
