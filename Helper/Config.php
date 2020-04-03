<?php
namespace Sga\IpRedirect\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    protected $_scopeConfig;

    const XML_PATH_ENABLED = 'ipredirect/general/enabled';
    const XML_PATH_LOG_ENABLED = 'ipredirect/general/log_redirects';
    const XML_PATH_EXCLUDE_IPS = 'ipredirect/general/exclude_ips';
    const XML_PATH_EXCLUDE_SEARCH_ENGINE = 'ipredirect/general/exclude_search_engines';
    const XML_PATH_EXCLUDE_SEARCH_ENGINE_NAMES = 'ipredirect/general/exclude_search_engine_names';
    const XML_PATH_EXCLUDE_PATTERNS_URL = 'ipredirect/general/exclude_patterns_url';
    const XML_PATH_MAPPING = 'ipredirect/general/mapping';

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Context $context
    ) {
        $this->_scopeConfig = $scopeConfig;

        parent::__construct($context);
    }

    public function isEnabled($store = null)
    {
        return $this->_scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function isLogEnabled($store = null)
    {
        return $this->_scopeConfig->isSetFlag(
            self::XML_PATH_LOG_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getExcludeIps($store = null)
    {
        return explode(',', $this->_scopeConfig->getValue(
            self::XML_PATH_EXCLUDE_IPS,
            ScopeInterface::SCOPE_STORE,
            $store
        ));
    }

    public function isExcludeSearchEngine($store = null)
    {
        return $this->_scopeConfig->isSetFlag(
            self::XML_PATH_EXCLUDE_SEARCH_ENGINE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getExcludeSearchEngineNames($store = null)
    {
        return explode("\n", $this->_scopeConfig->getValue(
            self::XML_PATH_EXCLUDE_SEARCH_ENGINE_NAMES,
            ScopeInterface::SCOPE_STORE,
            $store
        ));
    }

    public function getExcludePatternsUrl($store = null)
    {
        return explode("\n", $this->_scopeConfig->getValue(
            self::XML_PATH_EXCLUDE_PATTERNS_URL,
            ScopeInterface::SCOPE_STORE,
            $store
        ));
    }

    public function getMapping($store = null)
    {
        $str = $this->_scopeConfig->getValue(
            self::XML_PATH_MAPPING,
            ScopeInterface::SCOPE_STORE,
            $store
        );
        return json_decode($str, true);
    }
}