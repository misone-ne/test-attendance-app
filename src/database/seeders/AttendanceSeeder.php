<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $user1 = User::where('email', 'user1@example.com')->firstOrFail();
        $user2 = User::where('email', 'user2@example.com')->firstOrFail();

        $this->createUser1Attendances($user1);
        $this->createUser2Attendances($user2);
    }

    private function createUser1Attendances(User $user): void
    {
        // 過去5ヶ月：各月 平日15日
        for ($i = 5; $i >= 1; $i--) {
            $month = now()->subMonths($i)->startOfMonth();
            $this->createMonthlyWeekdayRecords($user, $month, 15);
        }

        // 当月17日分
        $currentMonth = now()->startOfMonth();
        $weekdays = $this->getWeekdays($currentMonth, 17);

        $patterns = array_merge(
            array_fill(0, 10, ['in' => [9, 0], 'out' => [18, 0], 'note' => '通常勤務']),
            array_fill(0, 3, ['in' => [9, 0], 'out' => [20, 0], 'note' => '残業']),
            array_fill(0, 2, ['in' => [9, 30], 'out' => [18, 0], 'note' => '遅刻']),
            [['in' => [9, 0], 'out' => [17, 0], 'note' => '早退']],
            [['in' => [8, 0], 'out' => [21, 0], 'note' => '長時間労働']]
        );

        foreach ($weekdays as $index => $date) {
            $this->createAttendanceWithBreak($user, $date, $patterns[$index]);
        }
    }

    private function createUser2Attendances(User $user): void
    {
        // user2 は実運用に近い確認用データ
        for ($i = 2; $i >= 0; $i--) {
            $month = now()->subMonths($i)->startOfMonth();
            $weekdays = $this->getWeekdays($month, 12);

            foreach ($weekdays as $index => $date) {
                $pattern = match ($index % 4) {
                    0 => ['in' => [9, 0], 'out' => [18, 0], 'note' => '通常勤務'],
                    1 => ['in' => [9, 30], 'out' => [18, 0], 'note' => '遅刻'],
                    2 => ['in' => [9, 0], 'out' => [17, 0], 'note' => '早退'],
                    default => ['in' => [9, 0], 'out' => [20, 0], 'note' => '残業'],
                };

                $this->createAttendanceWithBreak($user, $date, $pattern);
            }
        }
    }

    private function createMonthlyWeekdayRecords(User $user, Carbon $month, int $count): void
    {
        $weekdays = $this->getWeekdays($month, $count);

        foreach ($weekdays as $date) {
            $this->createAttendanceWithBreak($user, $date, [
                'in' => [9, 0],
                'out' => [18, 0],
                'note' => '通常勤務',
            ]);
        }
    }

    private function getWeekdays(Carbon $month, int $count): array
    {
        $weekdays = [];
        $date = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        while ($date->lte($endOfMonth)) {
            if ($date->isWeekday()) {
                $weekdays[] = $date->copy();
            }

            $date->addDay();
        }

        if (count($weekdays) <= $count) {
            return $weekdays;
        }

        $selectedDates = [];

        for ($i = 0; $i < $count; $i++) {
            $index = (int) round($i * (count($weekdays) - 1) / ($count - 1));
            $selectedDates[] = $weekdays[$index];
        }

        return $selectedDates;
    }

    private function createAttendanceWithBreak(User $user, Carbon $date, array $pattern): void
    {
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in' => $date->copy()->setTime($pattern['in'][0], $pattern['in'][1]),
            'clock_out' => $date->copy()->setTime($pattern['out'][0], $pattern['out'][1]),
            'note' => $pattern['note'],
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => $date->copy()->setTime(12, 0),
            'break_end' => $date->copy()->setTime(13, 0),
        ]);
    }
}
