<?php
namespace S3Sync\Console\Commands;

use Aws\Common\Credentials\Credentials;
use Exception;
use S3Sync\Config;
use Aws\S3\S3Client;
use S3Sync\Historical;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeployCommand extends Command
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
        $this->setName('deploy');
        $this->setDescription('Generates the log.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getApplication();
        $app->find('inspect')->run(
            new ArrayInput(['-q']),
            $output
        );

        $this->config = $app->getConfig();
        $this->historical = $app->getHistorical();
        $this->input = $input;
        $this->output = $output;

        $this->s3 = S3Client::factory([
            'region'        => $this->config->region,
            'credentials'   => new Credentials(
                $this->config->aws_key,
                $this->config->aws_secret
            ),
        ]);

        $this->deploy();
        $this->historical->save();
    }

    /**
     * Sends the files to AWS S3 Cloud
     */
    protected function deploy()
    {
        foreach($this->historical->actions['remove'] as $file => $md5) {
            if($this->remove($file)) {
                unset($this->historical->actions['remove'][$file]);
                $this->historical->files[$file] = $md5;
            }
        }

        foreach($this->historical->actions['add'] as $file => $md5) {
            if($this->add($file)) {
                unset($this->historical->actions['add'][$file]);
                $this->historical->files[$file] = $md5;
            }
        }
    }

    /**
     * @param  string $file
     * @return string string
     */
    protected function realPath($file)
    {
        return $this->config->getDirectory() . DIRECTORY_SEPARATOR . $file;
    }

    /**
     * @param  string $file
     * @return string
     */
    protected function fileToKey($file)
    {
        return str_replace('\\', '/', $this->config->folder . '/' . $file);
    }

    /**
     * @param  $file
     * @return bool
     */
    protected function remove($file)
    {
        try {
            $this->s3->deleteObject(array(
                'Bucket' => $this->config->bucket,
                'Key' => $this->fileToKey($file),
            ));
        } catch (Exception $e) {
            $this->output->writeln($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * @param $file
     * @return bool
     */
    protected function add($file)
    {
        try {
            $this->s3->putObject([
                'Bucket' => $this->config->bucket,
                'Key'    => $this->fileToKey($file),
                'SourceFile' => $this->realPath($file)
            ]);
        } catch (Exception $e) {
            $this->output->writeln($e->getMessage());
            return false;
        }

        return true;
    }
}