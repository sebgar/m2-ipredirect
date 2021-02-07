<?php
namespace Sga\IpRedirect\Api\Data;

interface LocationInterface
{
    const LOCATION_ID = 'location_id';
    const START_IP_NUM = 'start_ip_num';
    const END_IP_NUM = 'end_ip_num';
    const COUNTRY = 'country';

    /**
     * Get location id
     *
     * @return int|null
     */
    public function getLocationId();

    /**
     * Set location id
     *
     * @param int $id
     * @return LocationInterface
     */
    public function setLocationId($id);
    
    /**
     * Get start_ip_num
     *
     * @return int|null
     */
    public function getStartIpNum();

    /**
     * Set start_ip_num
     *
     * @param int $value
     * @return LocationInterface
     */
    public function setStartIpNum($value);
    
    /**
     * Get end_ip_num
     *
     * @return int|null
     */
    public function getEndIpNum();

    /**
     * Set end_ip_num
     *
     * @param int $value
     * @return LocationInterface
     */
    public function setEndIpNum($value);
    
    /**
     * Get country
     *
     * @return string|null
     */
    public function getCountry();

    /**
     * Set country
     *
     * @param string $value
     * @return LocationInterface
     */
    public function setCountry($value);
    
}