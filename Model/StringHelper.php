<?php

namespace ShoppingFeed\Manager\Model;

use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Locale\ResolverInterface as LocaleResolverInterface;

class StringHelper
{
    const ICONV_CHARSET = 'UTF-8';

    /**
     * @var LocaleResolverInterface
     */
    private $localeResolver;

    /**
     * @var FilterManager
     */
    private $filterManager;

    /**
     * @var \Collator|null
     */
    private $collator = null;

    /**
     * @param LocaleResolverInterface $localeResolver
     * @param FilterManager $filterManager
     */
    public function __construct(LocaleResolverInterface $localeResolver, FilterManager $filterManager)
    {
        $this->localeResolver = $localeResolver;
        $this->filterManager = $filterManager;
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
     * @param string $value
     * @param int $offset
     * @param int $length
     * @return string
     */
    public function substr($value, $offset, $length = null)
    {
        if (is_null($length)) {
            $length = iconv_strlen($value, static::ICONV_CHARSET) - $offset;
        }

        return iconv_substr($value, $offset, $length, static::ICONV_CHARSET);
    }

    /**
     * @param string $stringA
     * @param string $stringB
     * @return int|false
     */
    public function strcmp($stringA, $stringB)
    {
        $collator = $this->getCollator();
        $collator->setAttribute(\Collator::NUMERIC_COLLATION, \Collator::OFF);
        return $collator->compare($stringA, $stringB);
    }

    /**
     * @param $stringA
     * @param $stringB
     * @return int|false
     */
    public function strnatcmp($stringA, $stringB)
    {
        $collator = $this->getCollator();
        $collator->setAttribute(\Collator::NUMERIC_COLLATION, \Collator::ON);
        return $collator->compare($stringA, $stringB);
    }

    /**
     * @param string $value
     * @return string
     */
    public function getNormalizedCode($value)
    {
        return preg_replace('/[^a-z_0-9]{1,}/', '_', $this->filterManager->translitUrl($value));
    }
}
