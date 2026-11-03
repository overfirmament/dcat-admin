<?php

namespace Tests\Feature\Form;

use Dcat\Admin\Form;
use PHPUnit\Framework\Attributes\Group;
use Tests\Models\User;
use Tests\TestCase;

/**
 * LimitTextarea length validation tests.
 */
#[Group('form:limit-textarea')]
class LimitTextareaTest extends TestCase
{
    protected $login = false;

    public function testDefaultLimitIsEnforcedServerSide()
    {
        $field = $this->form()->limitTextarea('profile.address');

        $this->assertSame(200, (int) $field->getAttribute('data-maxlength'));
        $this->assertTrue($field->hasRule('max:200'));

        $this->assertFalse($this->validate($field, str_repeat('a', 200))->fails());
        $this->assertTrue($this->validate($field, str_repeat('a', 201))->fails());
    }

    public function testMaxLengthOverridesDefaultLimit()
    {
        $field = $this->form()->limitTextarea('profile.address')->maxLength(10);

        $this->assertSame(10, (int) $field->getAttribute('data-maxlength'));
        $this->assertFalse($field->hasRule('max:200'));
        $this->assertTrue($field->hasRule('max:10'));

        $this->assertFalse($this->validate($field, str_repeat('a', 10))->fails());
        $this->assertTrue($this->validate($field, str_repeat('a', 11))->fails());
    }

    public function testMaxLengthReplacesPreviousRule()
    {
        $field = $this->form()->limitTextarea('profile.address');

        $field->maxLength(10)->maxLength(5);

        $this->assertFalse($field->hasRule('max:10'));
        $this->assertTrue($field->hasRule('max:5'));

        $field->creationRules('max:100');
        $field->maxLength(6);

        $this->assertFalse($field->hasCreationRule('max:100'));
        $this->assertSame(6, (int) $field->getAttribute('data-maxlength'));
        $this->assertTrue($this->validate($field, str_repeat('a', 7))->fails());
    }

    public function testMaxLengthCountsUnicodeCodePoints()
    {
        $field = $this->form()->limitTextarea('profile.address')->maxLength(2);

        $this->assertFalse($this->validate($field, '😀😀')->fails());
        $this->assertTrue($this->validate($field, '😀😀😀')->fails());
    }

    public function testMaxLengthUsesLocalizedServerMessage()
    {
        app()->setLocale('zh_CN');

        $field = $this->form()->limitTextarea('profile.address')->maxLength(2);

        $this->assertSame(
            trans('admin.validation.maxlength'),
            $field->getValidationMessages()['profile.address.max']
        );

        $this->assertSame(
            str_replace([':attribute', ':max'], [$field->label(), 2], trans('admin.validation.maxlength')),
            $this->validate($field, 'abc')->errors()->first('profile.address')
        );
    }

    public function testClientValidatorIsRegisteredOnlyOnce()
    {
        $form = $this->form();

        $form->limitTextarea('profile.address');
        $form->limitTextarea('profile.biography');

        $script = app('admin.asset')->scriptToHtml();

        $this->assertSame(1, substr_count($script, "Dcat.validator.extend('maxlength'"));
        $this->assertStringContainsString("Array.from(\$el.val() || '').length", $script);
    }

    public function testTextFieldUsesSharedLengthValidation()
    {
        $maxField = $this->form()->text('username')->maxLength(5);
        $minField = $this->form()->text('email')->minLength(3);

        $this->assertSame(
            5,
            (int) $maxField->getAttribute('data-maxlength')
        );
        $this->assertTrue($maxField->hasRule('max:5'));
        $this->assertTrue($maxField->getValidator(['username' => 'abcdef'])->fails());
        $this->assertFalse($maxField->getValidator(['username' => 'abcde'])->fails());

        $this->assertSame(
            3,
            (int) $minField->getAttribute('data-minlength')
        );
        $this->assertTrue($minField->hasRule('min:3'));
        $this->assertTrue($minField->getValidator(['email' => 'ab'])->fails());
        $this->assertFalse($minField->getValidator(['email' => 'abc'])->fails());
    }

    protected function form()
    {
        return new Form(new User());
    }

    protected function validate($field, string $value)
    {
        return $field->getValidator([
            'profile' => [
                'address' => $value,
            ],
        ]);
    }
}
