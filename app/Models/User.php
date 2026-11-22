<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable; // Import Contract
use OwenIt\Auditing\Auditable as AuditableTrait; // Import Trait
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements Auditable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable,AuditableTrait,HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'nik',
        'email',
        'password',
        'whatsapp',
        'jobsite_id',
        'department_id',
        'position_id',
        'pp_path',
        'so_path',
        'jd_path',
        'manager_id',
        'position_level_id',
        'is_active'
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
     * Anggota tim IT (department IT, lihat config app.it_department_id).
     */
    public function isItStaff(): bool
    {
        return (int) $this->department_id === (int) config('app.it_department_id');
    }

    public function isItAdmin(): bool
    {
        // Logika ini memeriksa apakah pengguna memiliki relasi 'position',
        // dan jika ada, apakah kode dari jabatan tersebut adalah 'IT_ADMIN'.
        // Pastikan Anda memiliki Jabatan "Admin IT" dengan kode 'IT_ADMIN' di database Anda.
        return $this->position && $this->position->name === 'Administrator IT';
    }
    public function jobsite(): BelongsTo
    {
        return $this->belongsTo(Jobsite::class);
    }
    public function department(): BelongsTo // <-- Tambahkan relasi ini
    {
        return $this->belongsTo(Department::class);
    }
    public function karyawans(): BelongsTo{
        return $this->belongsTo(KaryawanErp::class, 'nik', 'NIK');
    }
    public function givenHandovers(): HasMany // BAST dimana user ini sebagai Giver
    {
        return $this->hasMany(AssetTransaction::class, 'giver_user_id');
    }

    public function receivedHandovers(): HasMany // BAST dimana user ini sebagai Receiver
    {
        return $this->hasMany(AssetTransaction::class, 'receiver_user_id');
    }

    public function createdHandovers(): HasMany // BAST yang dibuat oleh user ini
    {
        return $this->hasMany(AssetTransaction::class, 'created_by_user_id');
    }
    public function reportedDamages(): HasMany
    {
        return $this->hasMany(AssetDamageReport::class, 'reported_by_user_id');
    }

    public function createdDamageReports(): HasMany
    {
        return $this->hasMany(AssetDamageReport::class, 'created_by_user_id');
    }
    public function position() { return $this->belongsTo(Position::class); }
    public function assetsHeld() { return $this->hasMany(Asset::class, 'holder_user_id'); }
    public function assetRequests(): HasMany
    {
        // Semua pengajuan yang dibuat oleh user ini
        return $this->hasMany(AssetRequest::class, 'requester_user_id');
    }
    public function manager(): BelongsTo // <-- TAMBAHKAN RELASI INI
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Mendapatkan semua bawahan langsung dari pengguna ini.
     */
    public function subordinates(): HasMany // <-- TAMBAHKAN RELASI INI
    {
        return $this->hasMany(User::class, 'manager_id');
    }
    public function approvalTasks(): HasMany
    {
        // Semua tugas approval yang ditujukan ke user ini
        return $this->hasMany(AssetRequestApproval::class, 'approver_user_id');
    }
    public function approvalLicense(){
        return $this->hasMany(LicenseRequestApproval::class, 'user_id');
    }
    public function tetrisScores(): HasMany
    {
        return $this->hasMany(TetrisScore::class)->orderBy('score', 'desc');
    }
    public function positionLevel(): BelongsTo
    {
        return $this->belongsTo(PositionLevel::class);
    }
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
    public function chats()
    {
        return $this->hasMany(Chat::class);
    }
    public function partTransactions(): HasMany
    {
        return $this->hasMany(PartTransaction::class);
    }
}
