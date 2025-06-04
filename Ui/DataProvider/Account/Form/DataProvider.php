<?php

namespace ShoppingFeed\Manager\Ui\DataProvider\Account\Form;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as BaseDataProvider;
use ShoppingFeed\Manager\Api\Data\AccountInterface;
use ShoppingFeed\Manager\Model\Account\RegistryConstants;
use ShoppingFeed\Manager\Model\Config\FieldFactoryInterface as ConfigFieldFactoryInterface;
use ShoppingFeed\Manager\Model\Config\Value\HandlerFactoryInterface as ConfigValueHandlerFactoryInterface;
use ShoppingFeed\Manager\Ui\DataProvider\Meta\Compatibility\Fixer as MetaCompatibilityFixer;

class DataProvider extends BaseDataProvider
{
    const FORM_NAMESPACE = 'sfm_account_token_form';

    const FIELD_ACCOUNT_ID = 'account_id';

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param Registry $coreRegistry
     * @param MetaCompatibilityFixer $metaCompatibilityFixer
     * @param ConfigFieldFactoryInterface $configFieldFactory
     * @param ConfigValueHandlerFactoryInterface $configValueHandlerFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        Registry $coreRegistry,
        array $meta = [],
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;

        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $this->prepareData($data)
        );
    }

    /**
     * @param array $data
     * @return array
     */
    private function prepareData(array $data)
    {
        /** @var AccountInterface $task */
        $account = $this->coreRegistry->registry(RegistryConstants::CURRENT_ACCOUNT);
        $accountId = $account->getId();

        if (!empty($accountId)) {
            $data[$accountId] = array_merge(
                $data[$accountId] ?? [],
                [ self::FIELD_ACCOUNT_ID => $accountId, ]
            );
        }

        return $data;
    }

    public function getData()
    {
        return $this->data;
    }
}
