<?php

namespace Tests\Browser\Cases\Form;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * LimitTextarea character counter tests.
 */
#[Group('form:limit-textarea')]
class LimitTextareaTest extends TestCase
{
    public function testCharacterCounter()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(admin_base_path('tests/users/create'))
                ->waitFor('textarea[name="profile[address]"]', 10)
                ->assertSeeIn('.limit_count', '(0/10)')
                ->type('textarea[name="profile[address]"]', 'abcdefghij')
                ->assertSeeIn('.limit_count', '(10/10)')
                ->assertMissing('.limit_count .text-danger')
                ->keys('textarea[name="profile[address]"]', 'k')
                ->assertSeeIn('.limit_count', '(11/10)')
                ->assertPresent('.limit_count .text-danger');
        });
    }
}
