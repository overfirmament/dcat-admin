<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Exception\AdminException;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait ImageField
{
    /**
     * Intervention calls.
     *
     * @var array
     */
    protected $interventionCalls = [];

    /**
     * Thumbnail settings.
     *
     * @var array
     */
    protected $thumbnails = [];

    protected static $interventionAlias = [
        'filling' => 'fill',
    ];

    /**
     * Default directory for file to upload.
     *
     * @return mixed
     */
    public function defaultDirectory()
    {
        return config('admin.upload.directory.image');
    }

    /**
     * Execute Intervention calls.
     *
     * @param  string  $target
     * @param  string|null  $mime
     * @return mixed
     */
    public function callInterventionMethods($target, $mime = null)
    {
        if (empty($this->interventionCalls)) {
            return $target;
        }

        $image = $this->imageManager()->read($target);

        foreach ($this->interventionCalls as $call) {
            $this->applyInterventionCall($image, $call['method'], $call['arguments']);
        }

        $image->save($target);

        return $target;
    }

    /**
     * @return \Intervention\Image\ImageManager
     */
    protected function imageManager()
    {
        return config('admin.upload.image.driver') === 'imagick'
            ? ImageManager::imagick()
            : ImageManager::gd();
    }

    /**
     * Apply a v2 style image operation through the v3 API.
     *
     * @param  \Intervention\Image\Interfaces\ImageInterface  $image
     * @param  string  $method
     * @param  array  $arguments
     * @return void
     */
    protected function applyInterventionCall($image, $method, array $arguments)
    {
        // v2 constraint callbacks are not part of the v3 API. Only strip
        // them for methods that accepted such callbacks in v2; v3 drawing
        // and text methods still accept callables.
        $hasConstraint = false;

        if (in_array($method, ['resize', 'fit', 'widen', 'heighten'], true)) {
            foreach ($arguments as $argument) {
                if ($argument instanceof \Closure) {
                    $hasConstraint = true;
                    break;
                }
            }

            $arguments = array_values(array_filter($arguments, function ($argument) {
                return ! $argument instanceof \Closure;
            }));
        }

        switch ($method) {
            case 'fit':
                $position = $arguments[3] ?? $arguments[2] ?? 'center';
                $image->cover($arguments[0], $arguments[1], is_string($position) ? $position : 'center');
                return;

            case 'widen':
                $image->scale($arguments[0], null);
                return;

            case 'heighten':
                $image->scale(null, $arguments[0]);
                return;

            case 'resize':
                // In v2 a resize callback commonly meant "keep aspect ratio".
                if ($hasConstraint || count($arguments) < 2) {
                    $image->scale($arguments[0] ?? null, $arguments[1] ?? null);
                } else {
                    $image->resize($arguments[0], $arguments[1]);
                }
                return;

            case 'insert':
                if (! method_exists($image, 'place')) {
                    break;
                }

                $image->place(...$arguments);
                return;

            case 'orientate':
                if (! method_exists($image, 'orient')) {
                    break;
                }

                $image->orient();
                return;

            case 'limitColors':
                if (! method_exists($image, 'reduceColors')) {
                    break;
                }

                $image->reduceColors(...$arguments);
                return;
        }

        if (! method_exists($image, $method)) {
            throw new AdminException(
                "Intervention Image v3 does not support [{$method}]. Please use the v3 API instead."
            );
        }

        $image->{$method}(...$arguments);
    }

    /**
     * Call intervention methods.
     *
     * @param  string  $method
     * @param  array  $arguments
     * @return $this
     *
     * @throws \Exception
     */
    public function __call($method, $arguments)
    {
        if (static::hasMacro($method)) {
            return parent::__call($method, $arguments);
        }

        if (! class_exists(ImageManager::class)) {
            throw new AdminException('To use image handling and manipulation, please install [intervention/image] v3 first.');
        }

        $this->interventionCalls[] = [
            'method'    => static::$interventionAlias[$method] ?? $method,
            'arguments' => $arguments,
        ];

        return $this;
    }

    /**
     * @param  string|array  $name
     * @param  int|null  $width
     * @param  int|null  $height
     * @return $this
     */
    public function thumbnail($name, ?int $width = null, ?int $height = null)
    {
        if (func_num_args() == 1 && is_array($name)) {
            foreach ($name as $key => $size) {
                if (count($size) >= 2) {
                    $this->thumbnails[$key] = $size;
                }
            }
        } elseif (func_num_args() == 3) {
            $this->thumbnails[$name] = [$width, $height];
        }

        return $this;
    }

    /**
     * Destroy original thumbnail files.
     *
     * @param  string|array  $file
     * @param  bool  $force
     * @return void
     */
    public function destroyThumbnail($file = null, bool $force = false)
    {
        if ($this->retainable && ! $force) {
            return;
        }

        $file = $file ?: $this->original;
        if (! $file) {
            return;
        }

        if (is_array($file)) {
            foreach ($file as $f) {
                $this->destroyThumbnail($f, $force);
            }

            return;
        }

        foreach ($this->thumbnails as $name => $_) {
            // We need to get extension type ( .jpeg , .png ...)
            $ext = pathinfo($file, PATHINFO_EXTENSION);

            // We remove extension from file name so we can append thumbnail type
            $path = Str::replaceLast('.'.$ext, '', $file);

            // We merge original name + thumbnail name + extension
            $path = $path.'-'.$name.'.'.$ext;

            if ($this->getStorage()->exists($path)) {
                $this->getStorage()->delete($path);
            }
        }
    }

    /**
     * Upload file and delete original thumbnail files.
     *
     * @param  UploadedFile  $file
     * @return $this
     */
    protected function uploadAndDeleteOriginalThumbnail(UploadedFile $file)
    {
        foreach ($this->thumbnails as $name => $size) {
            // We need to get extension type ( .jpeg , .png ...)
            $ext = pathinfo($this->name, PATHINFO_EXTENSION);

            // We remove extension from file name so we can append thumbnail type
            $path = Str::replaceLast('.'.$ext, '', $this->name);

            // We merge original name + thumbnail name + extension
            $path = $path.'-'.$name.'.'.$ext;

            $image = $this->imageManager()->read($file->getRealPath());

            $action = $size[2] ?? 'resize';
            $width = $size[0] ?? null;
            $height = $size[1] ?? null;

            // Keep the v2 thumbnail behavior: resize within the given box
            // while preserving the aspect ratio.
            if ($action === 'resize') {
                $image->scaleDown($width, $height);
            } elseif ($action === 'fit') {
                $image->cover($width, $height);
            } elseif (method_exists($image, $action)) {
                $image->{$action}($width, $height);
            } else {
                throw new AdminException(
                    "Intervention Image v3 does not support thumbnail action [{$action}]."
                );
            }

            $encoded = $image->encodeByPath($path);

            if (! is_null($this->storagePermission)) {
                $this->getStorage()->put("{$this->getDirectory()}/{$path}", $encoded->toFilePointer(), $this->storagePermission);
            } else {
                $this->getStorage()->put("{$this->getDirectory()}/{$path}", $encoded->toFilePointer());
            }
        }

        if (! is_array($this->original)) {
            $this->destroyThumbnail();
        }

        return $this;
    }
}
