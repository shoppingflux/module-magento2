<?php

namespace ShoppingFeed\Manager\Model;

use Magento\Framework\Locale\ResolverInterface\Proxy as LocaleResolverProxy;

class StringHelper
{
    /**
     * @var LocaleResolverProxy
     */
    private $localeResolver;

    /**
     * @var \Collator|null
     */
    private $collator = null;

    /**
     * @param LocaleResolverProxy $localeResolverProxy
     */
    public function __construct(LocaleResolverProxy $localeResolverProxy)
    {
        $this->localeResolver = $localeResolverProxy;
    }

    /**
     * @return \Collator
     */
    private function getCollator()
    {
        if (null === $this->collator) {
            $this->collator = new \Collator($this->localeResolver->getLocale());
        }

        return $this->collator;
    }

    /**
     * @param string $stringA
     * @param string $stringB
     * @return int|false
     */
    public function strcmp($stringA, $stringB)
    {
        return $this->getCollator()->compare($stringA, $stringB);
    }
}
