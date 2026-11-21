<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_sw',
        'name_en',
        'description_sw',
        'description_en',
        'icon',
        'color',
    ];

    public static function defaultDepartments(): array
    {
        return [
            [
                'name_sw' => 'Idara ya Wamama (WWI)',
                'name_en' => "Women's Department (WWI)",
                'description_sw' => 'Idara ya ushirika wa wamama (WWI).',
                'description_en' => "Women's fellowship department (WWI).",
                'icon' => 'fa-female',
                'color' => '#ec4899',
            ],
            [
                'name_sw' => 'Idara ya Wababa',
                'name_en' => "Men's Department",
                'description_sw' => 'Idara ya ushirika wa wababa.',
                'description_en' => "Men's fellowship department.",
                'icon' => 'fa-male',
                'color' => '#1e40af',
            ],
            [
                'name_sw' => 'Idara ya Vijana (CGM)',
                'name_en' => "Youth Department (CGM)",
                'description_sw' => 'Idara inayokutanisha vijana (CGM) kwa ajili ya kumtumikia Mungu.',
                'description_en' => "Youth department (CGM) serving God.",
                'icon' => 'fa-users',
                'color' => '#10b981',
            ],
            [
                'name_sw' => 'Idara ya Watoto (CWI)',
                'name_en' => "Children's Department (CWI)",
                'description_sw' => 'Idara inayoshughulika na malezi ya kiroho kwa watoto (CWI).',
                'description_en' => "Children's spiritual upbringing department (CWI).",
                'icon' => 'fa-child',
                'color' => '#3b82f6',
            ],
            [
                'name_sw' => 'Idara ya Kusifu na Kuabudu (KUSKI)',
                'name_en' => "Praise & Choir Department (KUSKI)",
                'description_sw' => 'Idara inayoshughulika na sifa, kuabudu na uimbaji wa kwaya (KUSKI).',
                'description_en' => "Praise, worship and choir singing department (KUSKI).",
                'icon' => 'fa-music',
                'color' => '#8b5cf6',
            ],
        ];
    }

    public static function ensureDefaultsExist(): void
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('departments')) {
                return;
            }

            // Rename old praise/choir department if exists
            static::where('name_sw', 'Idara ya Praise & Kwaya (KUSKI)')
                ->update(['name_sw' => 'Idara ya Kusifu na Kuabudu (KUSKI)']);

            foreach (static::defaultDepartments() as $dept) {
                static::updateOrInsert(
                    ['name_sw' => $dept['name_sw']],
                    $dept
                );
            }
        } catch (\Throwable $e) {
            // Ignore if DB not ready
        }
    }

    /**
     * Get the users belonging to this department.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role', 'cheo')
            ->withTimestamps();
    }

    public function contributionCampaigns(): HasMany
    {
        return $this->hasMany(ContributionCampaign::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
