<?php

namespace Tests\Browser\Cases\Form;

use Illuminate\Support\Facades\Storage;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Models\User;
use Tests\TestCase;

/**
 * 图片上传测试.
 */
#[Group('form:image')]
class ImageTest extends TestCase
{
    public function testUploadImage()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(admin_base_path('tests/users/create'))
                ->waitFor('.web-uploader', 10)
                ->type('username', 'image-user')
                ->type('email', 'image@example.com')
                ->type('password', 'secret-password')
                ->type('password_confirmation', 'secret-password')
                ->attach('input[name="_file_"]', test_resource_path('assets/test.jpg'))
                ->waitFor('.filelist li', 10)
                ->click('.upload-btn')
                ->waitUntil("document.querySelector('input[name=avatar]').value.length > 0", 20)
                ->press(__('admin.submit'))
                ->waitForLocation(admin_base_path('tests/users'), 20);
        });

        $user = User::query()->where('username', 'image-user')->firstOrFail();
        $disk = Storage::disk(config('admin.upload.disk'));

        $this->assertNotEmpty($user->avatar);
        $this->assertTrue($disk->exists($user->avatar));

        $size = getimagesize($disk->path($user->avatar));

        $this->assertNotFalse($size);
        $this->assertSame(100, $size[0]);
        $this->assertSame(100, $size[1]);

        $disk->delete($user->avatar);
    }
}
