<?php
namespace S3Sync;

trait JsonFileManipulator
{
    /**
     * @param string $filepath
     * @return array
     */
    public function readFile($filepath)
    {
        if(file_exists($filepath)) {
            $content = json_decode(file_get_contents($filepath), true);

            if(! is_null($content)) {
                return $content;
            }
        }

        return [];
    }

    /**
     * @param  string $filepath
     * @param  array $content
     * @return int
     */
    public function saveFile($filepath, $content)
    {
        return file_put_contents(
            $filepath,
            json_encode($content, JSON_PRETTY_PRINT)
        );
    }
}