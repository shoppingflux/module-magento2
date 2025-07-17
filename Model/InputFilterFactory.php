<?php

namespace ShoppingFeed\Manager\Model;

class InputFilterFactory
{
    public const ALLOW_EMPTY = 'allowEmpty';
    public const BREAK_CHAIN = 'breakChainOnFailure';
    public const DEFAULT_VALUE = 'default';
    public const MESSAGES = 'messages';
    public const ESCAPE_FILTER = 'escapeFilter';
    public const FIELDS = 'fields';
    public const FILTER = 'filter';
    public const FILTER_CHAIN = 'filterChain';
    public const MISSING_MESSAGE = 'missingMessage';
    public const INPUT_NAMESPACE = 'inputNamespace';
    public const VALIDATOR_NAMESPACE = 'validatorNamespace';
    public const FILTER_NAMESPACE = 'filterNamespace';
    public const NOT_EMPTY_MESSAGE = 'notEmptyMessage';
    public const PRESENCE = 'presence';
    public const PRESENCE_OPTIONAL = 'optional';
    public const PRESENCE_REQUIRED = 'required';
    public const RULE = 'rule';
    public const RULE_WILDCARD = '*';
    public const VALIDATE = 'validate';
    public const VALIDATOR = 'validator';
    public const VALIDATOR_CHAIN = 'validatorChain';
    public const VALIDATOR_CHAIN_COUNT = 'validatorChainCount';

    public function getNotEmptyRuleValue()
    {
        return !class_exists('\Magento\Framework\Filter\FilterInput')
            ? 'NotEmpty'
            : 'Magento\Framework\Validator\NotEmpty';
    }

    /**
     * @param array $filterRules
     * @param array $validatorRules
     * @param array|null $data
     * @param array|null $options
     * @return \Magento\Framework\Filter\FilterInput|\Zend_Filter_Input
     * @throws \Exception
     */
    public function getInputFilter(array $filterRules, array $validatorRules, ?array $data = null, ?array $options = null)
    {
        // Work around a new EQP restriction.
        // \Magento\Framework\Filter\FilterInput was not yet available on all versions from the 2.3.x branch.
        $zendFilterClass = implode('_', [ 'Zend', 'Filter', 'Input' ]);

        if (class_exists($zendFilterClass)) {
            return new $zendFilterClass($filterRules, $validatorRules, $data, $options);
        } elseif (class_exists('\Magento\Framework\Filter\FilterInput')) {
            return new \Magento\Framework\Filter\FilterInput($filterRules, $validatorRules, $data, $options);
        }

        throw new \Exception('No input filter class found.');
    }

    /**
     * @param \Exception $e
     * @return bool
     */
    public function isFilterException(\Exception $e)
    {
        if (class_exists('Zend_Filter_Exception')) {
            return $e instanceof \Zend_Filter_Exception;
        } elseif (class_exists('\Magento\Framework\Filter\FilterException')) {
            return $e instanceof \Magento\Framework\Filter\FilterException;
        }

        return false;
    }
}
