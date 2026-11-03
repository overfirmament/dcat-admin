<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Admin;

/**
 * Shared maxlength/minlength validation for form fields.
 */
trait ValidatesLength
{
    /**
     * Whether the length limit has been configured explicitly.
     *
     * @var bool
     */
    protected bool $lengthLimitConfigured = false;

    /**
     * Set the maximum length of the field.
     *
     * @param  int  $length
     * @param  string|null  $error
     * @return $this
     */
    public function maxLength(int $length, ?string $error = null): static
    {
        $this->registerMaxLengthValidator();

        $this->lengthLimitConfigured = true;

        $this->replaceLengthRule('max');
        $this->rules('max:'.$length, array_merge(
            $this->validationMessages['default'] ?? [],
            ['max' => trans('admin.validation.maxlength')]
        ));

        return $this->attribute([
            'data-maxlength'       => $length,
            'data-maxlength-error' => $this->formatLengthMessage('maxlength', ':max', $length, $error),
        ]);
    }

    /**
     * Set the minimum length of the field.
     *
     * @param  int  $length
     * @param  string|null  $error
     * @return $this
     */
    public function minLength(int $length, ?string $error = null)
    {
        $this->registerMinLengthValidator();

        $this->replaceLengthRule('min');
        $this->rules(($this->hasRule('nullable') ? '' : 'nullable|').'min:'.$length, array_merge(
            $this->validationMessages['default'] ?? [],
            ['min' => trans('admin.validation.minlength')]
        ));

        return $this->attribute([
            'data-minlength'       => $length,
            'data-minlength-error' => $this->formatLengthMessage('minlength', ':min', $length, $error),
        ]);
    }

    /**
     * Register the client side maxlength validator.
     *
     * @return void
     */
    protected function registerMaxLengthValidator()
    {
        Admin::script(
            <<<'JS'
Dcat.validator.extend('maxlength', function ($el) {
    return Array.from($el.val() || '').length > $el.attr('data-maxlength');
});
JS
        );
    }

    /**
     * Register the client side minlength validator.
     *
     * @return void
     */
    protected function registerMinLengthValidator()
    {
        Admin::script(
            <<<'JS'
Dcat.validator.extend('minlength', function ($el) {
    return Array.from($el.val() || '').length < $el.attr('data-minlength');
});
JS
        );
    }

    /**
     * Remove a previously configured length rule.
     *
     * @param  string  $rule
     * @return void
     */
    protected function replaceLengthRule(string $rule)
    {
        $pattern = '/^'.preg_quote($rule, '/').':\d+$/';

        foreach (['rules', 'creationRules', 'updateRules'] as $property) {
            if (! is_array($this->{$property})) {
                continue;
            }

            $this->{$property} = array_values(array_filter(
                $this->{$property},
                function ($value) use ($pattern) {
                    return ! is_string($value) || ! preg_match($pattern, $value);
                }
            ));
        }
    }

    /**
     * Format the length validation message.
     *
     * @param  string  $key
     * @param  string  $placeholder
     * @param  int  $length
     * @param  string|null  $error
     * @return string
     */
    protected function formatLengthMessage(string $key, string $placeholder, int $length, ?string $error = null): string
    {
        return str_replace(
            [':attribute', $placeholder],
            [$this->label, $length],
            $error ?: trans('admin.validation.'.$key)
        );
    }
}
