<?php

namespace ShoppingFeed\Manager\Model\Sales\Invoice\Pdf;

use ShoppingFeed\Manager\Api\Sales\Invoice\Pdf\ProcessorInterface;
use ShoppingFeed\Manager\Api\Sales\Invoice\Pdf\ProcessorPoolInterface;

class ProcessorPool implements ProcessorPoolInterface
{
    /**
     * @var ProcessorInterface[]
     */
    private $processors;

    /**
     * @param ProcessorInterface[] $processors
     */
    public function __construct(array $processors)
    {
        foreach ($processors as $processor) {
            if (!$processor instanceof ProcessorInterface) {
                throw new \InvalidArgumentException(
                    sprintf('Invoice PDF processor must implement %s', ProcessorInterface::class)
                );
            }

            $this->processors[$processor->getCode()] = $processor;
        }
    }

    public function getProcessors()
    {
        return $this->processors;
    }

    public function getProcessorByCode($code)
    {
        return $this->getProcessors()[$code] ?? null;
    }
}
