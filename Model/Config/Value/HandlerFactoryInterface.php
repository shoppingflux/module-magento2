<?php

namespace ShoppingFeed\Manager\Model\Config\Value;


interface HandlerFactoryInterface
{
    /**
     * @param string $typeCode
     * @param array $data
     * @return HandlerInterface
     */
    public function create($typeCode, array $data = []);
}

