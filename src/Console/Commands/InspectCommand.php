<?php
namespace S3Sync\Console\Commands;

use S3Sync\Config;
use S3Sync\Historical;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InspectCommand extends Command
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Historical
     */
    protected $historical;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * Configures
     */
    protected function configure()
    {
        $this->setName('inspect');
        $this->setDescription('Generates the log.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getApplication();
        $this->config = $app->getConfig();
        $this->historical = $app->getHistorical();
        $this->historical->prepare();
        $this->input = $input;
        $this->output = $output;

        $this->inspect(
            $this->getFiles($this->config->getDirectory())
        );
        $this->historical->save();
    }

    /**
     * @param $path
     * @return Finder|\Symfony\Component\Finder\SplFileInfo[]
     */
    protected function getFiles($path)
    {
        return (new Finder())
            ->ignoreDotFiles(false)
            ->ignoreVCS(true)
            ->ignoreUnreadableDirs()
            ->files()
            ->in($path)
            ->exclude('vendor');
    }

    /**
     * @param Finder $files
     */
    protected function inspect(Finder $files)
    {
        $progress = new ProgressBar($this->output, $files->count());
        $progress->setFormat('debug');
        $progress->start();

        foreach($files as $file) {
            if($file->isFile()) {
                $this->historical->analyze($file);
            }
            $progress->advance();
        }
        $progress->finish();
    }
}