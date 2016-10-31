<?php

namespace S3Sync;

use ArrayObject;
use InvalidArgumentException;
use Symfony\Component\Finder\SplFileInfo;

class Historical extends ArrayObject
{
    use JsonFileManipulator;

    /**
     * Historical constructor.
     * @param array $filepath
     */
    public function __construct($filepath)
    {
        $this->filepath = $filepath;
        $this->directory = realpath(parse_url($filepath, PHP_URL_HOST));

        parent::__construct(
            array_merge(
                $this->structure(),
                $this->readFile($filepath)
            ),
            ArrayObject::ARRAY_AS_PROPS
        );
    }

    /**
     * @return array
     */
    protected function structure()
    {
        return [
            'files' => [],
            'actions' => [
                'add' => [],
                'remove' => [],
            ],
            'inspected' => time(),
        ];
    }

    /**
     * Saves the config.
     * @return int
     */
    public function save()
    {
        return $this->saveFile(
            $this->filepath,
            $this->getArrayCopy()
        );
    }

    /**
     * Prepare arrays to make an inspection.
     */
    public function prepare()
    {
        $this->actions['add'] = [];
        $this->actions['remove'] = (array) $this->files;
    }

    /**
     * @param  string $action
     * @param  SplFileInfo $file
     * @return Historical
     */
    public function action($action, SplFileInfo $file, $md5 = null)
    {
        $this->actions[$action][$file->getRelativePathname()] = $md5;

        return $this;
    }

    /**
     * @param SplFileInfo $file
     * @return Historical
     */
    public function add(SplFileInfo $file)
    {
        return $this->action('add', $file, md5_file($file));
    }

    /**
     * @param SplFileInfo $file
     * @return Historical
     */
    public function remove(SplFileInfo $file)
    {
        return $this->action('remove', $file);
    }

    /**
     * @param SplFileInfo
     */
    public function analyze(SplFileInfo $file)
    {
        if($this->has($file)) {
            unset($this->actions['remove'][$file->getRelativePathname()]);

            if(! $this->compareEquals($file)) {
                $this->add($file);
                $this->remove($file);
            }
        } else {
            $this->add($file);
        }
    }

    /**
     * Verify if file has already been mapped.
     * @param SplFileInfo $file
     * @return bool
     */
    public function has(SplFileInfo $file)
    {
        return isset($this->files[$file->getRelativePathname()]);
    }

    /**
     * @param SplFileInfo $file
     * @return bool
     */
    public function compareEquals(SplFileInfo $file)
    {
        if(! $this->has($file)) {
            throw new InvalidArgumentException('The '.$file.' doesnt exists so it can\'t be compared.');
        }

        return $this->files[$file->getRelativePathname()] == md5_file($file);
    }
}