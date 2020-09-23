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
     * @return bool
     */
    private function hasAlwaysTrueDisableTmplInstructions()
    {
        $magentoVersion = $this->appMetadata->getVersion();

        /**
         * The value of "__disableTmpl" instructions is not taken into account by Magento 2.1.18 / 2.2.9 / 2.3.2:
         * https://github.com/magento/magento2/blob/2.1.18/lib/web/mage/utils/template.js#L56
         * https://github.com/magento/magento2/blob/2.2.9/lib/web/mage/utils/template.js#L56
         * https://github.com/magento/magento2/blob/2.3.2/lib/web/mage/utils/template.js#L56
         *
         * This was fixed in Magento 2.2.10 / 2.3.3:
         * https://github.com/magento/magento2/blob/2.2.10/lib/web/mage/utils/template.js#L60
         * https://github.com/magento/magento2/blob/2.3.3/lib/web/mage/utils/template.js#L60
         */
        return (version_compare($magentoVersion, '2.1.18') >= 0)
            && (version_compare($magentoVersion, '2.1.19') < 0)
            || (version_compare($magentoVersion, '2.2.9') >= 0)
            && (version_compare($magentoVersion, '2.2.10') < 0)
            || (version_compare($magentoVersion, '2.3.2') >= 0)
            && (version_compare($magentoVersion, '2.3.3') < 0);
    }

    /**
     * @param array $configuration
     */
    public function fixMetaConfiguration(array $configuration)
    {
        if ($this->hasAlwaysTrueDisableTmplInstructions()) {
            $this->removeSwitcherRulesDisableTmplInstructions($configuration);
        }

        return $configuration;
    }
}
