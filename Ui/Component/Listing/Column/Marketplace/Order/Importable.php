<?php

namespace ShoppingFeed\Manager\Ui\Component\Listing\Column\Marketplace\Order;

class Importable extends AbstractColumn
{
    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');

            foreach ($dataSource['data']['items'] as &$item) {
                $isImportable = $this->isImportableOrderItem($item);

                if (null !== $isImportable) {
                    $item[$fieldName] = (int) $isImportable;
                }
            }
        }

        return $dataSource;
    }
}
