<?php

namespace ShoppingFeed\Manager\Ui\DataProvider\Meta\Compatibility;

use Magento\Framework\App\ProductMetadataInterface as AppMetadataInterface;

class Fixer
{
    /**
     * @var AppMetadataInterface
     */
    private $appMetadata;

    public function __construct(AppMetadataInterface $appMetadata)
    {
        $this->appMetadata = $appMetadata;
    }

    /**
     * @param array $rule
     */
    private function removeSwitcherRuleDisableTmplInstructions(array &$rule)
    {
        if (isset($rule['actions']) && is_array($rule['actions'])) {
            foreach ($rule['actions'] as &$action) {
                if (is_array($action) && isset($action['__disableTmpl'])) {
                    unset($action['__disableTmpl']);
                }
            }
        }
    }

    /**
     * @param array $configuration
     */
    private function removeSwitcherRulesDisableTmplInstructions(array &$configuration)
    {
        foreach ($configuration as $key => &$data) {
            if (is_array($data)) {
                if (
                    ('switcherConfig' === $key)
                    && !empty($data['enabled'])
                    && isset($data['rules'])
                    && is_array($data['rules'])
                ) {
                    foreach ($data['rules'] as $ruleKey => &$rule) {
                        if (is_array($rule)) {
                            $this->removeSwitcherRuleDisableTmplInstructions($rule);
                        }
                    }
                } else {
                    $this->removeSwitcherRulesDisableTmplInstructions($data);
                }
            }
        }
    }

    /**
     * @param array $configuration
     */
    public function fixMetaConfiguration(array $configuration)
    {
        if (
            (version_compare($this->appMetadata->getVersion(), '2.3.2') >= 0)
            && (version_compare($this->appMetadata->getVersion(), '2.3.3') < 0)
        ) {
            /**
             * The value of "__disableTmpl" instructions is not taken into account by Magento 2.3.2:
             * https://github.com/magento/magento2/blob/2.3.2/lib/web/mage/utils/template.js#L56
             *
             * This was fixed in Magento 2.3.3:
             * https://github.com/magento/magento2/blob/2.3.3/lib/web/mage/utils/template.js#L60
             */
            $this->removeSwitcherRulesDisableTmplInstructions($configuration);
        }

        return $configuration;
    }
}
