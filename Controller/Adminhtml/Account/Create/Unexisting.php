<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account\Create;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Model\Account\Importer as AccountImporter;

class Unexisting extends Action
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::account_create_unexisting';

    /**
     * @var AccountImporter
     */
    private $accountImporter;

    /**
     * @param Context $context
     * @param AccountImporter $accountImporter
     */
    public function __construct(Context $context, AccountImporter $accountImporter)
    {
        $this->accountImporter = $accountImporter;
        parent::__construct($context);
    }

    public function execute()
    {
        // @todo
        $redirectResult = $this->resultRedirectFactory->create();
        return $redirectResult->setPath('*/*/create_form_existing');
    }
}
