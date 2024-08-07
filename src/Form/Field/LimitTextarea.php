<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Admin;

class LimitTextarea extends Textarea
{
    protected $view = "admin::form.limit-textarea";

    protected ?string $message = "";

    protected int $limit = 200;

    public function render()
    {
        $this->addVariables([
            'message' => $this->message,
            'limit' => $this->limit,
        ]);

        return parent::render();
    }


    /**
     * @param  int  $length
     * @param  string|null  $error
     *
     * @return LimitTextarea
     */
    public function maxLength(int $length, ?string $error = null)
    {
        $this->limit = $length;

        Admin::script(
            <<<'JS'
    Dcat.validator.extend('maxlength', function ($el) {
    return $el.val().length > $el.attr('data-maxlength');
});
JS
        );

        $originalMessages = $this->validationMessages["default"] ?? [];
        $messages = array_merge($originalMessages, ["max" => "最多输入{$length}个字符"]);

        $this->rules('max:'.$length, $messages);

        return $this->attribute([
            'data-maxlength'       => $length,
            'data-maxlength-error' => str_replace(
                [':attribute', ':max'],
                [$this->label, $length],
                $error ?: trans('admin.validation.maxlength')
            ),
        ]);
    }
}