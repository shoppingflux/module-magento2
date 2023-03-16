<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Feed;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use ShoppingFeed\Manager\Block\Adminhtml\AbstractButton;
use ShoppingFeed\Manager\Block\Adminhtml\SplitButton;
use ShoppingFeed\Manager\Model\Account\Store\RegistryConstants;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;

class ForceRefreshButton extends AbstractButton
{
    /**
     * @var SectionTypePoolInterface
     */
    private $sectionTypePool;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param SectionTypePoolInterface $sectionTypePool
     * @param string|null $aclResource
     * @param string|null $name
     * @param string|null $label
     * @param string|null $urlRoute
     * @param array|null $urlParams
     * @param string|null $class
     * @param string|null $sortOrder
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        SectionTypePoolInterface $sectionTypePool,
        $aclResource = null,
        $name = null,
        $label = null,
        $urlRoute = null,
        $urlParams = null,
        $class = null,
        $sortOrder = null
    ) {
        $this->sectionTypePool = $sectionTypePool;

        parent::__construct(
            $context,
            $coreRegistry,
            $aclResource,
            $name,
            $label,
            $urlRoute,
            $urlParams,
            $class,
            $sortOrder
        );
    }

    /**
     * @param string $refreshUrl
     * @return string
     */
    private function getRefreshButtonAction($refreshUrl)
    {
        return 'confirmSetLocation('
            . '\'' . __('Are you sure you want to force a feed refresh?') . '\', '
            . '\'' . $refreshUrl . '\''
            . ')';
    }

    public function getButtonData()
    {
        $sectionOptions = [
            [
                'label' => __('Everything'),
                'onclick' => $this->getRefreshButtonAction(
                    $this->getUrl(
                        '*/*/forceFeedRefresh',
                        [
                            'store_id' => $this->getAccountStoreId(),
                            'export_state' => 1,
                            'section_type' => 'all',
                        ]
                    )
                ),
            ],
            [
                'label' => __('Export State'),
                'onclick' => $this->getRefreshButtonAction(
                    $this->getUrl(
                        '*/*/forceFeedRefresh',
                        [
                            'store_id' => $this->getAccountStoreId(),
                            'export_state' => 1,
                        ]
                    )
                ),
            ],
            [
                'label' => __('All Sections'),
                'onclick' => $this->getRefreshButtonAction(
                    $this->getUrl(
                        '*/*/forceFeedRefresh',
                        [
                            'store_id' => $this->getAccountStoreId(),
                            'section_type' => 'all',
                        ]
                    )
                ),
            ],
        ];

        foreach ($this->sectionTypePool->getSortedTypes() as $sectionType) {
            $sectionOptions[] = [
                'label' => __('%1 Section', $sectionType->getLabel()),
                'onclick' => $this->getRefreshButtonAction(
                    $this->getUrl(
                        '*/*/forceFeedRefresh',
                        [
                            'store_id' => $this->getAccountStoreId(),
                            'section_type' => $sectionType->getId(),
                        ]
                    )
                ),
            ];
        }

        return [
            'label' => __('Force Feed Refresh'),
            'class_name' => SplitButton::class,
            'class' => 'secondary',
            'button_class' => 'secondary',
            'options' => $sectionOptions,
        ];
    }

    /**
     * @return int|null
     */
    public function getAccountStoreId()
    {
        $store = $this->coreRegistry->registry(RegistryConstants::CURRENT_ACCOUNT_STORE);
        return $store ? $store->getId() : null;
    }
}
