<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account\Create;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Model\Account\Importer as AccountImporter;


class Existing extends Action
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::account_create_existing';

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
        $redirectResult = $this->resultRedirectFactory->create();

        if (is_array($accountData = $this->getRequest()->getParam('account'))) {
            $apiToken = false;
            $storeData = $this->getRequest()->getParam('store');

            if (!is_array($storeData)) {
                $storeData = [];
            }

            try {
                if (empty($accountData['use_api_token'])) {
                    if (!empty($accountData['shopping_feed_login'])
                        && !empty($accountData['shopping_feed_password'])
                    ) {
                        $apiToken = $this->accountImporter->getApiTokenByLogin(
                            $accountData['shopping_feed_login'],
                            $accountData['shopping_feed_password']
                        );
                    }
                } elseif (!empty($accountData['api_token'])) {
                    $apiToken = trim($accountData['api_token']);
                }

                if (empty($apiToken)) {
                    throw new LocalizedException(__('The Shopping Feed account API token could not be determined.'));
                }

                $this->accountImporter->importAccountByApiToken(
                    $apiToken,
                    (bool) ($storeData['import_main_store'] ?? false),
                    (int) ($storeData['base_store_id'] ?? 0)
                );

                $this->messageManager->addSuccessMessage(__('The account has been successfully created.'));
                return $redirectResult->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addExceptionMessage($e->getPrevious() ?: $e);
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('An error occurred while creating the account.'));
            }
        }

        return $redirectResult->setPath('*/*/create_form_existing');
    }
}
