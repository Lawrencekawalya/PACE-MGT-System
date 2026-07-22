<?php

namespace App\Models;

use Database\Factories\SchoolSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'official_name',
    'short_name',
    'slogan',
    'country_code',
    'timezone',
    'date_format',
    'time_format',
    'logo_path',
    'self_test_pass_mark',
    'pace_test_pass_mark',
    'self_test_retry_limit',
])]
class SchoolSetting extends Model
{
    /** @use HasFactory<SchoolSettingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'self_test_pass_mark' => 'decimal:2',
            'pace_test_pass_mark' => 'decimal:2',
            'self_test_retry_limit' => 'integer',
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate(['id' => 1], self::defaults());
    }

    /**
     * @return array<string, string|int>
     */
    public static function defaults(): array
    {
        return [
            'official_name' => 'Friends International Christian Academy',
            'short_name' => 'FICA',
            'slogan' => '#1 ACE Mission School in Uganda',
            'country_code' => 'UG',
            'timezone' => 'Africa/Kampala',
            'date_format' => 'DD/MM/YYYY',
            'time_format' => '12-hour',
            'self_test_pass_mark' => '80.00',
            'pace_test_pass_mark' => '80.00',
            'self_test_retry_limit' => 2,
        ];
    }
}
