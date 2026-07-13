<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Factoryで共通して使用するパスワードを保持する。
     *
     * @var string|null
     */
    protected static ?string $password;

    /**
     * Userモデルのデフォルトのダミーデータを定義する。
     *
     * @return array<string, mixed> ユーザーのダミーデータ
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * メール未認証状態のユーザーデータを定義する。
     *
     * @return static メール未認証状態を設定したFactory
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
