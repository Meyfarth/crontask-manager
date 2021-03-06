<?php

namespace Meyfarth\CrontaskBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Meyfarth\CrontaskBundle\Service\CrontaskService;

/**
 * Crontask
 */
class Crontask
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $commands;

    /**
     * @var integer
     */
    private $commandInterval;

    /**
     * @var \DateTime
     */
    private $firstRun;

    /**
     * @var \DateTime
     */
    private $lastRun;

    /**
     * @var boolean
     */
    private $isActive;

    /**
     * @var integer
     */
    private $intervalType;

    public function __construct(){
        $this->intervalType = CrontaskService::INTERVAL_TYPE_SECONDS;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Crontask
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set commands
     *
     * @param array $commands
     * @return Crontask
     */
    public function setCommands($commands)
    {
        $this->commands = $commands;

        return $this;
    }

    /**
     * Get commands
     *
     * @return array 
     */
    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * Set commandInterval
     *
     * @param integer $commandInterval
     * @return Crontask
     */
    public function setCommandInterval($commandInterval)
    {
        $this->commandInterval = $commandInterval;

        return $this;
    }

    /**
     * Get commandInterval
     *
     * @return integer 
     */
    public function getCommandInterval()
    {
        return $this->commandInterval;
    }

    /**
     * Set firstRun
     *
     * @param \DateTime $firstRun
     * @return Crontask
     */
    public function setFirstRun($firstRun)
    {
        $this->firstRun = $firstRun;

        return $this;
    }

    /**
     * Get firstRun
     *
     * @return \DateTime 
     */
    public function getFirstRun()
    {
        return $this->firstRun;
    }

    /**
     * Set lastRun
     *
     * @param \DateTime $lastRun
     * @return Crontask
     */
    public function setLastRun($lastRun)
    {
        $this->lastRun = $lastRun;

        return $this;
    }

    /**
     * Get lastRun
     *
     * @return \DateTime 
     */
    public function getLastRun()
    {
        return $this->lastRun;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return Crontask
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean 
     */
    public function getIsActive()
    {
        return $this->isActive;
    }


    public function __toString(){
        return $this->getName();
    }

    /**
     * Set intervalType
     *
     * @param integer $intervalType
     * @return Crontask
     */
    public function setIntervalType($intervalType)
    {
        $this->intervalType = $intervalType;

        return $this;
    }

    /**
     * Get intervalType
     *
     * @return integer 
     */
    public function getIntervalType()
    {
        return $this->intervalType;
    }
}
