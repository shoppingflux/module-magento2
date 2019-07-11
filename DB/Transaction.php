<?php

namespace ShoppingFeed\Manager\DB;

use Magento\Framework\DB\Transaction as BaseTransaction;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb as AbstractResourceModel;

class Transaction extends BaseTransaction
{
    /**
     * @var AbstractResourceModel[]
     */
    private $resourceModels = [];

    /**
     * @param AbstractResourceModel $resourceModel
     * @return $this
     */
    public function addResourceModel(AbstractResourceModel $resourceModel)
    {
        $this->resourceModels[] = $resourceModel;
        return $this;
    }

    /**
     * @param AbstractModel $model
     * @return $this
     */
    public function addModelResource(AbstractModel $model)
    {
        $this->addResourceModel($model->getResource());
        return $this;
    }

    protected function _startTransaction()
    {
        parent::_startTransaction();

        foreach ($this->resourceModels as $resourceModel) {
            $resourceModel->beginTransaction();
        }

        return $this;
    }

    protected function _commitTransaction()
    {
        parent::_commitTransaction();

        foreach ($this->resourceModels as $resourceModel) {
            $resourceModel->commit();
        }

        return $this;
    }

    protected function _rollbackTransaction()
    {
        parent::_rollbackTransaction();

        foreach ($this->resourceModels as $resourceModel) {
            $resourceModel->rollBack();
        }

        return $this;
    }
}
