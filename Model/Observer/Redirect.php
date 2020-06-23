<?php
namespace Sga\IpRedirect\Model\Observer;

use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\Session;
use Sga\IpRedirect\Helper\Config;
use Sga\IpRedirect\Model\ResourceModel\Location\Collection;

class Redirect implements ObserverInterface
{
    protected $_helperConfig;
    protected $_actionFlag;
    protected $_storeManager;
    protected $_request;
    protected $_remoteAddress;
    protected $_session;
    protected $_cookieManager;
    protected $_locationCollection;

    const COOKIE_GEOIP_REDIRECT = 'geoip_redirect';

    public function __construct(
        Context $context,
        Config $helperConfig,
        ActionFlag $actionFlag,
        StoreManagerInterface $storeManager,
        RemoteAddress $remoteAddress,
        Session $session,
        CookieManagerInterface $cookieManager,
        Collection $locationCollection
    ){
        $this->_helperConfig = $helperConfig;
        $this->_actionFlag = $actionFlag;
        $this->_storeManager = $storeManager;
        $this->_request = $context->getRequest();
        $this->_remoteAddress = $remoteAddress;
        $this->_session = $session;
        $this->_cookieManager = $cookieManager;
        $this->_locationCollection = $locationCollection;
    }

    public function execute(Observer $observer)
    {
        if ($this->_helperConfig->isEnabled()) {
            $visitorIp = $this->_getVisitorsIp();

            // is ip excluded
            if ($this->_excludeThisVisitorFromRedirect($visitorIp)) {
                return false;
            }

            // is search bot
            if ($this->_helperConfig->isExcludeSearchEngine() && $this->_isSearchEngineBot()) {
                return false;
            }

            // is url excluded
            if ($this->_isPatternUrlMatch()) {
                return false;
            }

            // determine country
            $countryCode = $this->_getVisitorsIpCountrySession();
            if ($countryCode === NULL) {
                $countryCode = $this->_getCountryCodeByIp($visitorIp);
                $this->_setVisitorsIpCountrySession($countryCode);
            }

            if (is_string($countryCode)) {
                $mapping = $this->_helperConfig->getMapping();
                if (is_array($mapping)) {
                    foreach ($mapping as $map) {
                        if (!$this->_getVisitorAlreadyRedirected() || !(bool)$map['redirect_once']) {
                            if ($countryCode === $map['country']) {
                                // flag visitor is redirected
                                $this->_setVisitorAlreadyRedirected();

                                // check if current url is the same as redirect url
                                if ($map['url'] == $this->_storeManager->getStore()->getBaseUrl()) {
                                    return false;
                                }

                                // log
                                if ($this->_helperConfig->isLogEnabled()) {
                                    Mage::log('Redirect '.$this->_getVisitorsIp() . ' (' . $this->_getVisitorsIpCountrySession() . ') redirected from ' . $this->_request->getCurrentUrl() . ' to ' . $map['url'], null, 'ipredirect.log');
                                }

                                $this->_actionFlag->set('', Action::FLAG_NO_DISPATCH, true);

                                $controllerAction = $observer->getEvent()->getControllerAction();
                                $controllerAction->getResponse()->setRedirect($map['url']);
                                return;
                            }
                        }
                    }
                }
            }
        }
    }

    protected function _isPatternUrlMatch()
    {
        $lines = $this->_helperConfig->getExcludePatternsUrl();
        $currentUrl = $this->_request->getRequestString();
        foreach ($lines as $line) {
            if (trim($line) != '') {
                if (preg_match('#^'.str_replace('*', '.*', $line).'$#', $currentUrl)) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function _getVisitorAlreadyRedirected()
    {
        return (bool)$this->_cookieManager->getCookie(self::COOKIE_GEOIP_REDIRECT);
    }

    protected function _setVisitorAlreadyRedirected()
    {
        $this->_cookieManager->setPublicCookie(self::COOKIE_GEOIP_REDIRECT, '1');
    }

    protected function _getVisitorsIp()
    {
        return $this->_remoteAddress->getRemoteAddress();
    }

    protected function _excludeThisVisitorFromRedirect($ip = false)
    {
        if ($ip == false) {
            $ip = $this->_getVisitorsIp();
        }

        $excludedIps = $this->_helperConfig->getExcludeIps();
        if (is_array($excludedIps) && count($excludedIps) > 0 && in_array($ip, $excludedIps)) {
            return true;
        }

        return false;
    }

    protected function _isSearchEngineBot()
    {
        $bots = $this->_helperConfig->getExcludeSearchEngineNames();
        if (is_array($bots)) {
            foreach ($bots as $b) {
                if (stripos($this->_request->getHeader('user-agent'), $b) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function _getVisitorsIpCountrySession()
    {
        return $this->_session->getData("_visitor_ip_country");
    }

    protected function _setVisitorsIpCountrySession($country_code)
    {
        $this->_session->setData("_visitor_ip_country", $country_code);
    }

    protected function _getCountryCodeByIp($ip = false)
    {
        if ($ip == false) {
            $ip = $this->_getVisitorsIp();
        }

        $longIP = sprintf("%u", ip2long($ip));

        $result = false;
        if (!empty($longIP)) {
            $this->_locationCollection->getSelect()
                ->where($longIP.' BETWEEN start_ip_num AND end_ip_num')
                ->order('location_id DESC');

            $location = $this->_locationCollection->getFirstItem();
            if ((int)$location->getLocationId() > 0) {
                $result = $location->getCountry();
            } else {
                $result = false;
            }
        }
        return $result;
    }
}
