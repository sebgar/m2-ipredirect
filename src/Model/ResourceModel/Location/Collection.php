<?php
namespace Sga\IpRedirect\Model\ResourceModel\Location;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Sga\IpRedirect\Model\Location as Model;
use Sga\IpRedirect\Model\ResourceModel\Location as ResourceModel;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'location_id';

    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}