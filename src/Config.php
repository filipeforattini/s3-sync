<?php
namespace S3Sync;

use ArrayObject;

class Config extends ArrayObject
{
    use JsonFileManipulator;

    /**
     * @var string
     */
    protected $filepath;

    /**
     * @var string
     */
    protected $directory;

    /**
     * Config constructor.
     * @param string $filepath
     */
    public function __construct($filepath)
    {
        $this->filepath = $filepath;
        $this->directory = realpath(parse_url($filepath, PHP_URL_HOST));

        parent::__construct(
            $this->readFile($filepath),
            ArrayObject::ARRAY_AS_PROPS
        );
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
     * Gets directory.
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }
}