<?php

namespace Tests\Feature;

use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\MessageBag;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * JSON session serialization compatibility tests.
 */
#[Group('session')]
class SessionSerializationTest extends TestCase
{
    protected $login = false;

    public function testJsonSessionConvertsMessageBagToArray()
    {
        $handler = new ArraySessionHandler(120);
        $store = new Store('dcat-admin-session-test', $handler, null, 'json');

        $store->start();
        $store->flash('success', new MessageBag([
            'title'   => 'Success title',
            'message' => 'Success message',
        ]));
        $store->save();

        $next = new Store('dcat-admin-session-test', $handler, $store->getId(), 'json');
        $next->start();

        $value = $next->get('success');

        $this->assertIsArray($value);
        $this->assertSame(['Success title'], $value['title']);

        session()->forget(['error', 'success', 'info', 'warning', 'errors']);
        session()->flash('success', $value);

        $html = view('admin::partials.alerts')->render();

        $this->assertStringContainsString('Success title', $html);
        $this->assertStringContainsString('Success message', $html);
    }

    public function testJsonSessionToastrArrayIsNormalized()
    {
        $value = json_decode(json_encode(new MessageBag([
            'message' => 'Toastr message',
            'type'    => 'warning',
            'options' => ['timeOut' => 1000],
        ])), true);

        session()->forget(['error', 'success', 'info', 'warning', 'errors', 'dcat-admin-toastr']);
        session()->flash('dcat-admin-toastr', $value);

        $html = view('admin::partials.toastr')->render();

        $this->assertStringContainsString('toastr.warning', $html);
        $this->assertStringContainsString('Toastr message', $html);
        $this->assertStringContainsString('timeOut', $html);
    }

    public function testMessageHelpersFlashArrays()
    {
        session()->forget(['error', 'success', 'info', 'warning', 'dcat-admin-toastr']);

        admin_success('Success title', 'Success message');
        admin_error('Error title', 'Error message');
        admin_warning('Warning title', 'Warning message');
        admin_info('Info title', 'Info message');
        admin_toastr('Toastr message', 'warning', ['timeOut' => 1000]);

        $this->assertSame([
            'title'   => 'Success title',
            'message' => 'Success message',
            'type'    => 'success',
        ], session()->get('success'));

        $this->assertSame([
            'title'   => 'Error title',
            'message' => 'Error message',
            'type'    => 'error',
        ], session()->get('error'));

        $this->assertSame([
            'title'   => 'Warning title',
            'message' => 'Warning message',
            'type'    => 'warning',
        ], session()->get('warning'));

        $this->assertSame([
            'title'   => 'Info title',
            'message' => 'Info message',
            'type'    => 'info',
        ], session()->get('info'));

        $this->assertSame([
            'message' => 'Toastr message',
            'type'    => 'warning',
            'options' => ['timeOut' => 1000],
        ], session()->get('dcat-admin-toastr'));
    }
}
