<?php

namespace ShoppingFeed\Manager\Model\String;

use Magento\Framework\Locale\ResolverInterface as LocaleResolverInterface;


class Helper
{
    /**
     * @var \Collator
     */
    private $collator;

    /**
     * @param LocaleResolverInterface $localeResolver
     */
    public function __construct(LocaleResolverInterface $localeResolver)
    {
        $this->collator = new \Collator($localeResolver->getLocale());
    }

    /**
     * @param string $stringA
     * @param string $stringB
     * @return int|false
     */
    public function strcmp($stringA, $stringB)
    {
        return $this->collator->compare($stringA, $stringB);
    }
}
