<?php

namespace S3Sync;

use Phar;
use SplFileInfo;
use Seld\PharUtils\Timestamps;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Finder\Finder;

class Compiler
{
    const FILE = 's3-sync.phar';

    /**
     * @var Phar
     */
    protected $phar;

    /**
     * @var string
     */
    protected $pharfile;

    /**
     * Compiler constructor.
     */
    public function __construct($pharFile = Compiler::FILE)
    {
        if (file_exists($pharFile)) {
            unlink($pharFile);
        }
        $phar = new Phar($pharFile, 0, static::FILE);
        $phar->setSignatureAlgorithm(Phar::SHA1);
        $this->phar = $phar;
        $this->pharfile = $pharFile;
    }

    /**
     * @param Finder $finder
     */
    public function addList(Finder $finder)
    {
        $progress = new ProgressBar(new ConsoleOutput(), $finder->count());
        foreach ($finder as $file) {
            $this->addFile($file);
            $progress->advance();
        }
        $progress->finish();
        return $this;
    }

    /**
     * @param callable $callable
     * @return Compiler
     */
    public function searchAndAddList(callable $callable)
    {
        return $this->addList(
            call_user_func_array($callable, [new Finder()])
        );
    }

    /**
     * @param string $filepath
     * @param string|null $path
     * @return Compiler
     */
    public function searchAndAdd($filepath) {
        return $this->addFile(new SplFileInfo($filepath));
    }

    /**
     * @param SplFileInfo $file
     * @param string|null $path
     * @return Compiler
     */
    private function addFile(SplFileInfo $file)
    {
        $path = strtr(str_replace(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR, '', $file->getRealPath()), '\\', '/');
        $content = file_get_contents($file);
        //$content .= PHP_EOL.'?'.PHP_EOL.PHP_EOL;
        //$content = $this->stripWhitespace($content);
        $this->phar->addFromString($path, $content);
        return $this;
    }

    /**
     * Runs compiler
     */
    public function run()
    {
        $pharFile = $this->pharfile;
        $phar = $this->phar;

        $phar->startBuffering();

        $this
            ->searchAndAddList(function(Finder $finder){
                return $finder
                    ->files()
                    ->ignoreVCS(true)
                    ->name('*.php')
                    ->notName('Compiler.php')
                    ->notName('ClassLoader.php')
                    ->exclude('vendor')
                    ->in(__DIR__)
                    ->sort(function ($a, $b) {
                        return strcmp(strtr($a->getRealPath(), '\\', '/'), strtr($b->getRealPath(), '\\', '/'));
                    });
            })
            ->searchAndAdd(__DIR__.'/Autoload/ClassLoader.php')
            ->searchAndAddList(function(Finder $finder) {
                return $finder
                    ->files()
                    ->ignoreVCS(true)
                    ->name('*.php')
                    ->exclude('tests')
                    ->exclude('Tests')
                    ->exclude('docs')
                    ->in(__DIR__.'/../vendor/symfony/')
                    ->in(__DIR__.'/../vendor/seld/phar-utils/')
                    ->in(__DIR__.'/../vendor/aws/aws-sdk-php/')
                    ->in(__DIR__.'/../vendor/guzzle/')
                    ->in(__DIR__.'/../vendor/psr/')
                    ->sort(function ($a, $b) {
                        return strcmp(strtr($a->getRealPath(), '\\', '/'), strtr($b->getRealPath(), '\\', '/'));
                    });
             })
            ->searchAndAdd(__DIR__.'/../vendor/autoload.php')
            ->searchAndAdd(__DIR__.'/../vendor/composer/autoload_namespaces.php')
            ->searchAndAdd(__DIR__.'/../vendor/composer/autoload_psr4.php')
            ->searchAndAdd(__DIR__.'/../vendor/composer/autoload_classmap.php')
            ->searchAndAdd(__DIR__.'/../vendor/composer/autoload_files.php')
            ->searchAndAdd(__DIR__.'/../vendor/composer/autoload_real.php')
            ->searchAndAdd(__DIR__.'/../vendor/composer/autoload_static.php')
            ->searchAndAdd(__DIR__.'/../vendor/composer/ClassLoader.php')
            ;

        $this->addBin();

        // Stubs
        $phar->setStub($this->getStub());

        $phar->stopBuffering();

        unset($phar);

        // re-sign the phar with reproducible timestamp / signature
        $util = new Timestamps($pharFile);
        $util->updateTimestamps(time());
        $util->save($pharFile, \Phar::SHA1);
    }

    /**
     * Adds the S3 Sync bin
     */
    private function addBin()
    {
        $content = file_get_contents(__DIR__.'/../bin/s3-sync');
        $content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
        $this->phar->addFromString('bin/s3-sync', $content);
    }

    /**
     * Removes whitespace from a PHP source string while preserving line numbers.
     *
     * @param  string $source A PHP string
     * @return string The PHP string with the whitespace removed
     */
    private function stripWhitespace($source)
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }

        $output = '';
        foreach (token_get_all($source) as $token) {
            if (is_string($token)) {
                $output .= $token;
            } elseif (in_array($token[0], array(T_COMMENT, T_DOC_COMMENT))) {
                $output .= str_repeat("\n", substr_count($token[1], "\n"));
            } elseif (T_WHITESPACE === $token[0]) {
                // reduce wide spaces
                $whitespace = preg_replace('{[ \t]+}', ' ', $token[1]);
                // normalize newlines to \n
                $whitespace = preg_replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
                // trim leading spaces
                $whitespace = preg_replace('{\n +}', "\n", $whitespace);
                $output .= $whitespace;
            } else {
                $output .= $token[1];
            }
        }

        return $output;
    }

    private function getStub()
    {
        $stub = <<<'EOF'
#!/usr/bin/env php
<?php
/*
 * This file is part of S3-Sync.
 *
 * (c) Filipe Forattini <filipeforattini1@gmail.com>
 *
 * For the full copyright and license information, please view
 * the license that is located at the bottom of this file.
 */

Phar::mapPhar('s3-sync.phar');

EOF;

        return $stub . <<<'EOF'
require 'phar://s3-sync.phar/bin/s3-sync';

__HALT_COMPILER();
EOF;
    }
}