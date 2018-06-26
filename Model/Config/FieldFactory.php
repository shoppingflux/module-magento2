<?php

namespace ShoppingFeed\Manager\Model\Config;

use Magento\Framework\Exception\LocalizedException;


class FieldFactory implements FieldFactoryInterface
{
    /**
     * @var array
     */
    private $typeFactories = [];

    /**
     * @param array $typeFactories
     * @throws LocalizedException
     */
    public function __construct(array $typeFactories = [])
    {
        foreach ($typeFactories as $typeCode => $typeFactory) {
            if (!is_callable([ $typeFactory, 'create' ])) {
                throw new LocalizedException(
                    __('Field type factory "%1" must implement a callable create() method.', $typeCode)
                );
            }

            $this->typeFactories[$typeCode] = $typeFactory;
        }
    }

    /**
     * @param string $typeCode
     * @param array $data
     * @return FieldInterface
     * @throws LocalizedException
     */
    public function create($typeCode, array $data)
    {
        if (!isset($this->typeFactories[$typeCode])) {
            throw new LocalizedException(__('Field type "%1" is not (yet) supported.', $typeCode));
        }

        $field = $this->typeFactories[$typeCode]->create($data);

        if (!$field instanceof FieldInterface) {
            throw new LocalizedException(
                __(
                    'Field type factory "%1" must create instances of type: ShoppingFeed\Manager\Model\Config\FieldInterface.',
                    $typeCode
                )
            );
        }

        return $field;
    }
}
