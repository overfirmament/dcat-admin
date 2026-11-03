<?php

namespace Dcat\Admin\Form\Field;


class LimitTextarea extends Textarea
{
    use ValidatesLength;

    protected $view = 'admin::form.limit-textarea';

    /**
     * Default maximum number of characters.
     *
     * @var int
     */
    protected int $defaultLimit = 200;

    /**
     * @param  string|array  $column
     * @param  array  $arguments
     */
    public function __construct($column, $arguments = [])
    {
        parent::__construct($column, $arguments);

        if (! $this->lengthLimitConfigured) {
            $this->maxLength($this->defaultLimit);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function defaultVariables()
    {
        return array_merge(parent::defaultVariables(), [
            'limit' => (int) ($this->getAttribute('data-maxlength') ?: $this->defaultLimit),
        ]);
    }
}
