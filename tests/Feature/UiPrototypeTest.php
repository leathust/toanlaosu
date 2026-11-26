<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('renders the responsive UI prototype shell', function () {
    $response = $this->get(route('ui.prototype'));

    $response->assertOk()
        ->assertSee('Toán Lão Sư')
        ->assertSee('Máy bay đi vào vùng radar')
        ->assertSee('Gia sư AI')
        ->assertSee('Thi online');
});

it('renders the UI component showcase with accessible states', function () {
    $response = $this->get(route('ui.components'));

    $response->assertOk()
        ->assertSee('UI-002 · Design system')
        ->assertSee('aria-valuenow="58"', false)
        ->assertSee('Không tải được dữ liệu')
        ->assertSee('Slug này đã được sử dụng.');
});

it('renders authentication and protected workspace states', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Đăng nhập để học tiếp')
        ->assertSee('Quên mật khẩu?');

    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('Bạn quên mật khẩu?');

    $user = User::factory()->create();

    $this->actingAs($user)->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('Kiểm tra hộp thư của bạn');

    $this->actingAs($user)->get(route('workspace'))
        ->assertOk()
        ->assertSee('Chọn không gian làm việc')
        ->assertSee('Tài khoản chưa được cấp workspace');
});

it('renders the student learning dashboard prototype', function () {
    $this->actingAs(User::factory()->create())->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('Resume learning')
        ->assertSee('Hàm số và chuyển động')
        ->assertSee('Khóa học đang theo dõi')
        ->assertSee('Trạng thái prototype')
        ->assertSee('Live data');
});

it('renders the lesson player with block states and scene controls', function () {
    $this->actingAs(User::factory()->create())->get(route('student.lesson'))
        ->assertOk()
        ->assertSee('Máy bay đi vào vùng radar')
        ->assertSee('Đã hoàn thành')
        ->assertSee('Đang học')
        ->assertSee('Thử thách')
        ->assertSee('Mô hình 2D')
        ->assertSee('Mock progress');
});

it('renders question types, hints and feedback states', function () {
    $this->actingAs(User::factory()->create())->get(route('student.practice'))
        ->assertOk()
        ->assertSee('Single choice')
        ->assertSee('Multiple choice')
        ->assertSee('Numeric input')
        ->assertSee('Xem gợi ý cấp 1')
        ->assertSee('Feedback sau lần thử 1')
        ->assertSee('MVP không có điểm âm');
});
