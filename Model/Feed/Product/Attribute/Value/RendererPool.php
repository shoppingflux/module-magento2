<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Exception\LocalizedException;


class RendererPool implements RendererPoolInterface
{
    /**
     * @var RendererInterface[]
     */
    private $renderers = [];

    /**
     * @var RendererInterface[]
     */
    private $sortedRenderers;

    /**
     * @param RendererInterface[] $renderers
     * @throws LocalizedException
     */
    public function __construct(array $renderers = [])
    {
        foreach ($renderers as $code => $renderer) {
            if (!$renderer instanceof RendererInterface) {
                throw new LocalizedException(
                    __(
                        'Attribute renderer %1 must be of type: ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\RendererInterface',
                        $code
                    )
                );
            }

            $this->renderers[$code] = $renderer;
        }
    }

    public function getRenderers()
    {
        return $this->renderers;
    }

    public function getSortedRenderers()
    {
        if (!is_array($this->sortedRenderers)) {
            $this->sortedRenderers = $this->getRenderers();

            usort(
                $this->sortedRenderers,
                function ($rendererA, $rendererB) {
                    /**
                     * @var RendererInterface $rendererA
                     * @var RendererInterface $rendererB
                     */
                    return $rendererA->getSortOrder() <=> $rendererB->getSortOrder();
                }
            );
        }

        return $this->sortedRenderers;
    }

    /**
     * @param string $code
     * @return RendererInterface
     * @throws LocalizedException
     */
    public function getRendererByCode($code)
    {
        if (isset($this->renderers[$code])) {
            return $this->renderers[$code];
        }

        throw new LocalizedException(__('Attribute renderer for code %1 does not exist', $code));
    }

    /**
     * @param AbstractAttribute $attribute
     * @return bool
     */
    public function hasAttributeRenderableValues(AbstractAttribute $attribute)
    {
        $isRenderable = false;

        foreach ($this->getRenderers() as $renderer) {
            if ($renderer->isAppliableToAttribute($attribute)) {
                $isRenderable = true;
                break;
            }
        }

        return $isRenderable;
    }
}
