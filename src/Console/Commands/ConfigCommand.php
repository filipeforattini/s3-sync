<?php
namespace S3Sync\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigCommand extends Command
{
    /**
     * @var \S3Sync\Config
     */
    protected $config;

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
        $this->setName('config');
        $this->setDescription('Configures local bucket');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getApplication();
        $this->config = $app->getConfig();
        $this->input = $input;
        $this->output = $output;

        $this->configureAws();

        $this->config->save();
    }

    /**
     * @param string $question_input
     * @param string $config
     * @param string|null $default
     * @return $this
     */
    protected function getUserInput($question_input, $config, $default = null)
    {
        $questioner = $this->getApplication()->getHelperSet()->get('question');

        $this->config->$config = $questioner->ask(
            $this->input,
            $this->output,
            new Question($question_input, $default)
        );

        return $this;
    }

    /**
     * Set of questions to configure AWS Access.
     */
    protected function configureAws()
    {
        $this->getUserInput('AWS access key ID: ', 'aws_key')
             ->getUserInput('AWS secret access key: ', 'aws_secret')
             ->getUserInput('Default region: ', 'region', 'sa-east-1')
             ->getUserInput('Define bucket: ', 'bucket')
             ->getUserInput('Define folder/key: ', 'folder');
    }
}