<?php

namespace ShoppingFeed\Manager\Model;

use Magento\Framework\Data\Collection\AbstractDb as Collection;
use Magento\Framework\Model\AbstractModel as BaseModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource as Resource;
use Magento\Framework\Registry;
use ShoppingFeed\Manager\Model\Time\Helper as TimeHelper;


abstract class AbstractModel extends BaseModel
{
    /**
     * @var TimeHelper
     */
    private $timeHelper;

    /**
     * @var string[]
     */
    protected $timestampFields = [];


    /**
     * @param Context $context
     * @param Registry $registry
     * @param TimeHelper $timeHelper
     * @param Resource|null $resource
     * @param Collection|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        TimeHelper $timeHelper,
        Resource $resource = null,
        Collection $resourceCollection = null,
        array $data = []
    ) {
        $this->timeHelper = $timeHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _afterLoad()
    {
        foreach ($this->timestampFields as $baseKey => $timestampKey) {
            if (($dateTimeValue = $this->_getData($baseKey))
                && preg_match('/^\d{4}-\d{2}\-\d{2}(?: \d{2}:\d{2}:\d{2})$/', $dateTimeValue)
            ) {
                $this->setData($timestampKey, strtotime($dateTimeValue));
            } else {
                $this->setData($timestampKey, null);
            }
        }

        parent::_afterLoad();
    }
}
