<?php

namespace A17\Blast;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;

class DataStore
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->data = [];
        $this->dataPath = config(
            'blast.data_path',
            'resources/views/stories/data',
        );
        $this->filesystem = $filesystem;

        $this->filesystem->ensureDirectoryExists($this->dataPath);
        $this->getComponentsData();
    }

    public function get($key = null)
    {
        if ($key) {
            return Arr::get($this->data, $key);
        }
    }

    private function getComponentsData()
    {
        if (!$this->filesystem->exists($this->dataPath)) {
            return 1;
        }

        $files = $this->filesystem->allfiles($this->dataPath);

        if (!empty($files)) {
            foreach ($files as $file) {
                if ($file->getExtension() == 'php') {
                    $filename = str_replace('.php', '', $file->getFilename());

                    $filePath = base_path(
                        $file->getPathname(),
                    );
                    if (file_exists($filePath)) {
                        $this->data[$filename] = include $filePath;
                    } else {
                        if (file_exists($file->getPathname())) {
                            $this->data[$filename] = include $file->getPathname();
                        }
                    }
                }
            }

            $this->data = array_map([$this, 'parsePresetArgs'], $this->data);
        }
    }
    private function parsePresetArgs($data = null)
    {
        $parsed = array_map(function ($item) {
            if (Arr::exists($item, 'presetArgs')) {
                $presetArgs = $item['presetArgs'];

                foreach ($presetArgs as $key => $presets) {
                    if (is_array($presets)) {
                        $args = array_map(function ($preset) {
                            return $this->get($preset)['args'] ?? [];
                        }, $presets);
                    } else {
                        $args = $this->get($presets)['args'] ?? [];
                    }

                    $item['args'][$key] = $args;
                }
            }

            return $item;
        }, $data);

        return $parsed;
    }
}
