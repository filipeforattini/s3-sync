<?php
namespace S3Sync\Console;

use S3Sync\Config;
use S3Sync\Historical;
use S3Sync\Console\Commands\DeployCommand;
use S3Sync\Console\Commands\ConfigCommand;
use S3Sync\Console\Commands\InspectCommand;
use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    /**
     * @var Config;
     */
    protected $config;

    /**
     * @var Historical
     */
    protected $historical;

    /**
     * Application constructor.
     * @param Config $config
     * @param string $name
     * @param string $version
     */
    public function __construct($name = 's3-sync', $version = 'beta')
    {
        parent::__construct('s3-sync', 'beta');

        $this->addCommands([
            new ConfigCommand(),
            new DeployCommand(),
            new InspectCommand(),
        ]);
    }

    /**
     * @param Config $config
     * @return Application
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param Historical $historical
     * @return Application
     */
    public function setHistorical(Historical $historical)
    {
        $this->historical = $historical;

        return $this;
    }

    /**
     * @return Historical
     */
    public function getHistorical()
    {
        return $this->historical;
    }
}