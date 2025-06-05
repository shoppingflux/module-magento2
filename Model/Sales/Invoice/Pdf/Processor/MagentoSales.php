<?php

namespace ShoppingFeed\Manager\Model\Sales\Invoice\Pdf\Processor;

use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Model\Order\Pdf\Invoice as InvoicePdfGenerator;
use ShoppingFeed\Manager\Api\Sales\Invoice\Pdf\ProcessorInterface;

class MagentoSales implements ProcessorInterface
{
    /**
     * @var InvoicePdfGenerator
     */
    private $pdfGenerator;

    /**
     * @param InvoicePdfGenerator $pdfGenerator
     */
    public function __construct(InvoicePdfGenerator $pdfGenerator)
    {
        $this->pdfGenerator = $pdfGenerator;
    }

    public function getCode()
    {
        return 'magento_sales';
    }

    public function getLabel()
    {
        return __('Default');
    }

    public function isAvailable()
    {
        return true;
    }

    public function getInvoicePdfContent(InvoiceInterface $invoice)
    {
        return (string) $this->pdfGenerator->getPdf([ $invoice ])->render();
    }
}