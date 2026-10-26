<?php

namespace Dcat\Admin\Form\Field;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Image field.
 *
 * Image manipulation calls are proxied to Intervention Image v3.
 * Common v3 methods: resize, scale, scaleDown, cover, contain, crop,
 * resizeCanvas, rotate, flip, brightness, contrast, gamma, sharpen,
 * blur, pixelate, greyscale, invert, colorize, trim, text.
 *
 * @method $this resize(?int $width = null, ?int $height = null)
 * @method $this scale(?int $width = null, ?int $height = null)
 * @method $this scaleDown(?int $width = null, ?int $height = null)
 * @method $this cover(int $width, int $height, string $position = 'center')
 * @method $this contain(int $width, int $height, mixed $background = 'ffffff', string $position = 'center')
 * @method $this crop(int $width, int $height, int $offsetX = 0, int $offsetY = 0, mixed $background = 'ffffff', string $position = 'top-left')
 * @method $this fit(int $width, int $height, string $position = 'center')
 * @method $this widen(int $width)
 * @method $this heighten(int $height)
 */
class Image extends File
{
    use ImageField;

    protected $rules = ['nullable', 'image'];

    protected $view = 'admin::form.file';

    public function __construct($column, $arguments = [])
    {
        parent::__construct($column, $arguments);

        $this->setupImage();
    }

    protected function setupImage()
    {
        if (! isset($this->options['accept'])) {
            $this->options['accept'] = [];
        }

        $this->options['accept']['mimeTypes'] = 'image/*';
        $this->options['isImage'] = true;
    }

    /**
     * @param  array  $options  support:
     *                          [
     *                          'width' => 100,
     *                          'height' => 100,
     *                          'min_width' => 100,
     *                          'min_height' => 100,
     *                          'max_width' => 100,
     *                          'max_height' => 100,
     *                          'ratio' => 3/2, // (width / height)
     *                          ]
     * @return $this
     */
    public function dimensions(array $options)
    {
        if (! $options) {
            return $this;
        }

        $this->mergeOptions(['dimensions' => $options]);

        foreach ($options as $k => &$v) {
            $v = "$k=$v";
        }

        return $this->rules('dimensions:'.implode(',', $options));
    }

    /**
     * Set ratio constraint.
     *
     * @param  float  $ratio  width/height
     * @return $this
     */
    public function ratio($ratio)
    {
        if ($ratio <= 0) {
            return $this;
        }

        return $this->dimensions(['ratio' => $ratio]);
    }

    /**
     * @param  UploadedFile  $file
     */
    protected function prepareFile(UploadedFile $file)
    {
        $this->callInterventionMethods($file->getRealPath(), $file->getMimeType());

        $this->uploadAndDeleteOriginalThumbnail($file);
    }
}
