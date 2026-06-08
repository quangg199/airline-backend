<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'membership_tier',  // Thêm cột hạng thành viên (standard, gold...)
    ];

    /**
     * Các thuộc tính tự động tính toán (Accessors) sẽ được serialize theo JSON API
     */
    protected $appends = [
        'business_class_ticket_count',
        'is_vip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * QUAN HỆ CƠ SỞ DỮ LIỆU
     */
     
    // 1 User có nhiều Booking (Quan hệ 1-n)
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
    public function roles()
    {
        // Một User có nhiều Role thông qua bảng trung gian role_user
        return $this->belongsToMany(Role::class);
    }

    /**
     * Đếm số lượng vé Hạng Thương Gia (business class) mà User đã thanh toán thành công.
     */
    public function getBusinessClassTicketCountAttribute()
    {
        return \App\Models\Ticket::whereHas('booking', function ($query) {
                // Đếm các booking của user này và có trạng thái hợp lệ (VD: paid)
                $query->where('user_id', $this->id)
                      ->where('status', 'paid');
            })
            ->whereHas('seat', function ($query) {
                $query->whereIn('seat_class', ['business', '2', 2]);
            })
            ->count();
    }

    /**
     * Khách hàng là VIP nếu có >= 5 vé thương gia.
     */
    public function getIsVipAttribute()
    {
        return $this->business_class_ticket_count >= 5;
    }
}