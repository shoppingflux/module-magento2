<?php

namespace ShoppingFeed\Manager\Model\Config\Value;

use Magento\Framework\Exception\LocalizedException;

class HandlerFactory implements HandlerFactoryInterface
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
                    __('Value handler factory "%1" must implement a callable create() method.', $typeCode)
                );
            }

            $this->typeFactories[$typeCode] = $typeFactory;
        }
    }

    /**
     * @param string $typeCode
     * @param array $data
     * @return HandlerInterface
     * @throws LocalizedException
     */
    public function create($typeCode, array $data = [])
    {
        if (!isset($this->typeFactories[$typeCode])) {
            throw new LocalizedException(__('Value handler type "%1" is not (yet) supported.'));
        }

        $handler = $this->typeFactories[$typeCode]->create($data);

        if (!$handler instanceof HandlerInterface) {
            throw new LocalizedException(
                __(
                    'Value handler factory "%1" must create instances of type: ShoppingFeed\Manager\Model\Config\Value\HandlerInterface.',
                    $typeCode
                )
            );
        }

        return $handler;
    }
}
