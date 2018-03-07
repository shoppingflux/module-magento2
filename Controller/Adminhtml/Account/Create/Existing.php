<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account\Create;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Model\Account\Importer as AccountImporter;
use ShoppingFeed\Manager\Model\ShoppingFeed\Api\Client as ApiClient;


class Existing extends Action
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::account_create_existing';

    /**
     * @var ApiClient
     */
    private $apiClient;

    /**
     * @var AccountImporter
     */
    private $accountImporter;

    /**
     * @param Context $context
     * @param ApiClient $apiClient
     * @param AccountImporter $accountImporter
     */
    public function __construct(Context $context, ApiClient $apiClient, AccountImporter $accountImporter)
    {
        $this->apiClient = $apiClient;
        $this->accountImporter = $accountImporter;
        parent::__construct($context);
    }

    public function execute()
    {
        $redirectResult = $this->resultRedirectFactory->create();

        if (is_array($accountData = $this->getRequest()->getParam('account'))) {
            $apiToken = false;

            try {
                if (empty($accountData['use_api_token'])) {
                    if (!empty($accountData['shopping_feed_username'])
                        && !empty($accountData['shopping_feed_password'])
                    ) {
                        $apiToken = $this->apiClient->getApiToken(
                            $accountData['shopping_feed_username'],
                            $accountData['shopping_feed_password']
                        );
                    }
                } elseif (!empty($accountData['api_token'])) {
                    $apiToken = trim($accountData['api_token']);
                }

                if (!empty($apiToken) && !empty($accountData['store_id'])) {
                    $apiAccount = $this->apiClient->getAccountData($apiToken);
                    $this->accountImporter->importFromApi($apiAccount, (int) $accountData['store_id']);
                }

                return $redirectResult->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addExceptionMessage($e->getPrevious() ?: $e);
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while creating the account.'));
            }
        }

        return $redirectResult->setPath('*/*/create_form_existing');
    }
}
