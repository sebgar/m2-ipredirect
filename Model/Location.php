<?php
namespace Sga\IpRedirect\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Sga\IpRedirect\Api\Data\LocationInterface as ModelInterface;
use Sga\IpRedirect\Model\ResourceModel\Location as ResourceModel;

class Location extends AbstractModel implements IdentityInterface, ModelInterface
{
    const CACHE_TAG = 'ipredirect_location';

    protected $_eventPrefix = 'ipredirect_location';

    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getLocationId()
    {
        return $this->getData(self::LOCATION_ID);
    }

    public function setLocationId($id)
    {
        return $this->setData(self::LOCATION_ID, $id);
    }

    public function getStartIpNum()
    {
        return $this->getData(self::START_IP_NUM);
    }

    public function setStartIpNum($value)
    {
        return $this->setData(self::START_IP_NUM, $value);
    }

    public function getEndIpNum()
    {
        return $this->getData(self::END_IP_NUM);
    }

    public function setEndIpNum($value)
    {
        return $this->setData(self::END_IP_NUM, $value);
    }

    public function getCountry()
    {
        return $this->getData(self::COUNTRY);
    }

    public function setCountry($value)
    {
        return $this->setData(self::COUNTRY, $value);
    }


}