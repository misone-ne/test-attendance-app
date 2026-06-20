<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $user1 = User::where('email', 'user1@example.com')->first();
        $user2 = User::where('email', 'user2@example.com')->first();

        $records = [
            [
                'user' => $user1,
                'date' => today(),
                'in' => [9, 0],
                'out' => [18, 0],
            ],
            [
                'user' => $user1,
                'date' => today()->subDay(),
                'in' => [9, 0],
                'out' => [18, 0],
            ],
            [
                'user' => $user1,
                'date' => today()->subDays(2),
                'in' => [9, 0],
                'out' => [18, 0],
            ],
        ];

        $records = array_merge($records, [
            [
                'user' => $user2,
                'date' => today(),
                'in' => [9, 30],
                'out' => [18, 0],
            ],
            [
                'user' => $user2,
                'date' => today()->subDay(),
                'in' => [9, 0],
                'out' => [17, 0],
            ],
            [
                'user' => $user2,
                'date' => today()->subDays(2),
                'in' => [9, 0],
                'out' => [20, 0],
            ],
        ]);

        foreach ($records as $record) {

            $attendance = Attendance::create([
                'user_id' => $record['user']->id,
                'work_date' => $record['date'],
                'clock_in' => $record['date']->copy()->setTime(
                    $record['in'][0],
                    $record['in'][1]
                ),
                'clock_out' => $record['date']->copy()->setTime(
                    $record['out'][0],
                    $record['out'][1]
                ),
            ]);

            BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_start' => $record['date']->copy()->setTime(12, 0),
                'break_end' => $record['date']->copy()->setTime(13, 0),
            ]);
        }
    }
}
