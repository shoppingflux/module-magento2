<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\Renderer;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\AbstractRenderer;

class Fpt extends AbstractRenderer
{
    public function getSortOrder()
    {
        return 0;
    }

    public function isAppliableToAttribute(AbstractAttribute $attribute)
    {
        return 'weee' === $attribute->getFrontendInput();
    }

    /**
     * @param array $row
     * @return bool
     */
    private function isValidWeeeRow(array $row)
    {
        return isset($row['website_id'])
            && isset($row['country'])
            && isset($row['state'])
            && isset($row['value']);
    }

    public function renderAttributeValue(StoreInterface $store, AbstractAttribute $attribute, $value)
    {
        if (is_array($value)) {
            $expectedWebsiteIds = [ 0, (int) $store->getBaseStore()->getWebsiteId() ];
            $countryRows = [];

            foreach ($value as $row) {
                $row['website_id'] = (int) $row['website_id'];
                $row['state'] = (int) $row['state'];
                $countryId = strtolower(trim($row['country']));
                $chosenRow = $countryRows[$countryId] ?? null;

                if (in_array($row['website_id'], $expectedWebsiteIds, true)
                    && empty($row['state'])
                    && (!is_array($chosenRow) || (empty($chosenRow['website_id']) && !empty($row['website_id'])))
                ) {
                    $countryRows[$countryId] = (float) $row['value'];
                }
            }

            $value = empty($countryRows) ? '' : $countryRows;
        }

        return $value;
    }
}
