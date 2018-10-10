<?php

namespace ShoppingFeed\Manager\Ui\Component\Listing\Column\Marketplace\Order;

use Magento\Framework\Locale\CurrencyInterface as LocaleCurrencyInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column as Column;

class Price extends Column
{
    /**
     * @var LocaleCurrencyInterface
     */
    private $localeCurrency;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param LocaleCurrencyInterface $localeCurrency
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        LocaleCurrencyInterface $localeCurrency,
        array $components = [],
        array $data = []
    ) {
        $this->localeCurrency = $localeCurrency;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     * @return array
     * @throws \Zend_Currency_Exception
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');

            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item[$fieldName])) {
                    $currency = null;

                    if (isset($item['currency_code'])) {
                        $currency = $this->localeCurrency->getCurrency($item['currency_code']);

                        if (null !== $currency) {
                            $item[$fieldName] = $currency->toCurrency((float) $item[$fieldName]);
                        }
                    }
                }
            }
        }

        return $dataSource;
    }
}
