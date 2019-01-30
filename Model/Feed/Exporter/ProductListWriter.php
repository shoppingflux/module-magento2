<?php

namespace ShoppingFeed\Manager\Model\Feed\Exporter;

use ShoppingFeed\Feed\Product\Product;
use ShoppingFeed\Feed\ProductFeedMetadata;
use ShoppingFeed\Feed\ProductFeedWriterInterface;

class ProductListWriter implements ProductFeedWriterInterface
{
    const ALIAS = 'sfm_product_list';

    /**
     * @var array
     */
    static private $uriProducts = [];

    /**
     * @var string
     */
    private $uri;

    public function open($uri)
    {
        $this->uri = $uri;
        self::$uriProducts[$uri] = [];
    }

    public function setAttributes(array $attributes)
    {
    }

    public function writeProduct(Product $product)
    {
        self::$uriProducts[$this->uri][] = $product;
    }

    public function close(ProductFeedMetadata $metadata)
    {
    }

    /**
     * @return Product[]
     */
    public function getProducts()
    {
        return self::$uriProducts[$this->uri];
    }

    /**
     * @param string $uri
     * @return Product[]
     */
    static public function getUriProducts($uri)
    {
        return isset(self::$uriProducts[$uri]) ? self::$uriProducts[$uri] : [];
    }
}
