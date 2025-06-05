<?php

namespace ShoppingFeed\Manager\Model\Sales\Invoice\Pdf\Processor;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\InvoiceInterface;
use ShoppingFeed\Manager\Api\Sales\Invoice\Pdf\ProcessorInterface;

class XtentoPdfCustomizer implements ProcessorInterface
{
    /**
     * @var \Xtento\PdfCustomizer\Helper\GeneratePdf|null
     */
    private $pdfGenerator;

    public function __construct()
    {
        try {
            $this->pdfGenerator = ObjectManager::getInstance()->get('\Xtento\PdfCustomizer\Helper\GeneratePdf');
        } catch (\Exception $e) {
            $this->pdfGenerator = null;
        }
    }

    public function getCode()
    {
        return 'xtento_pdf_customizer';
    }

    public function getLabel()
    {
        return __('Xtento PDF Customizer (Default Template)');
    }

    public function isAvailable()
    {
        return (null !== $this->pdfGenerator);
    }

    public function getInvoicePdfContent(InvoiceInterface $invoice)
    {
        if (null === $this->pdfGenerator) {
            throw new LocalizedException(__('Xtento PDF Customizer module is not installed.'));
        }

        try {
            $result = $this->pdfGenerator->generatePdfForObject('Invoice', $invoice->getId());

            return (string) ($result['output'] ?? '');
        } catch (\Exception $e) {
            throw new LocalizedException(__('Failed to generate PDF: %1', $e->getMessage()));
        }
    }
}
