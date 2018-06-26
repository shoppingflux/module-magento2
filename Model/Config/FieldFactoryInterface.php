<?php

namespace ShoppingFeed\Manager\Model\Config;


interface FieldFactoryInterface
{
    /**
     * @param string $typeCode
     * @param array $data
     * @return FieldInterface
     */
    public function create($typeCode, array $data);
}
