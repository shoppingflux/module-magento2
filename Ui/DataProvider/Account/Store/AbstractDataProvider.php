<?php

namespace ShoppingFeed\Manager\Ui\DataProvider\Account\Store;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as BaseDataProvider;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface as AccountStoreInterface;
use ShoppingFeed\Manager\Model\Account\Store\RegistryConstants;
use ShoppingFeed\Manager\Model\Feed\Product\Export\State\ConfigInterface as ExportStateConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;


abstract class AbstractDataProvider extends BaseDataProvider
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var ExportStateConfigInterface
     */
    protected $exportStateConfig;

    /**
     * @var SectionTypePoolInterface
     */
    protected $sectionTypePool;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param Registry $coreRegistry
     * @param ExportStateConfigInterface $stateConfig
     * @param SectionTypePoolInterface $sectionTypePool
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        Registry $coreRegistry,
        ExportStateConfigInterface $stateConfig,
        SectionTypePoolInterface $sectionTypePool,
        array $meta = [],
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->exportStateConfig = $stateConfig;
        $this->sectionTypePool = $sectionTypePool;

        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
    }

    /**
     * @return AccountStoreInterface
     */
    protected function getAccountStore()
    {
        return $this->coreRegistry->registry(RegistryConstants::CURRENT_ACCOUNT_STORE);
    }
}
