<?php

namespace ShoppingFeed\Manager\Model;

use Magento\Framework\Locale\ResolverInterface as LocaleResolverInterface;

class StringHelper
{
    /**
     * @var LocaleResolverInterface
     */
    private $localeResolver;

    /**
     * @var \Collator|null
     */
    private $collator = null;

    /**
     * @param LocaleResolverInterface $localeResolver
     */
    public function __construct(LocaleResolverInterface $localeResolver)
    {
        $this->localeResolver = $localeResolver;
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
