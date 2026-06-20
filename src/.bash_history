echo $SHELL
pwd
whoami
grep "^$(whoami):" /etc/passwd
ps -p $$ -o comm=
which bash
pwd
php artisan make:request AdminLoginRequest
php artisan make:seeder AdminSeeder
php artisan db:seed --class=AdminSeeder
exit
php artisan make:request AdminAttendanceUpdateRequest
php artisan migrate:fresh --seed
php artisan tinker
php artisan make:seeder UserSeeder
php artisan make:seeder AttendanceSeeder
php artisan migrate:fresh --seed
php artisan migrate:fresh --seed
exit
php artisan route:list | grep login
php artisan migrate:fresh --seed
exit
