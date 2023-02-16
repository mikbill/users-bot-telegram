<?php

namespace App\Console\Commands;

use App\Models\TelegramUsers;
use App\Notifications\BotNotification;
use Illuminate\Console\Command;
use Kagatan\MikBillClientAPI\ClientAPI;
use WeStacks\TeleBot\Laravel\TelegramNotification;

class LeftDayCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:left-day
                                    {number : count day}
                                    { --send : send notification users }
                                    { --show : show list}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Команда оповещения абонента за n-дней до отключения';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $text = "Уважаемый Максим, до отключения осталось 3 дня. Пополните ваш счет.";

        $numberDay = $this->argument('number');

        $show = $this->option('show');
        $send = $this->option('send');

        // Получим список абонентов
        $tgUsers = TelegramUsers::whereNotNull('token')->get();

        // Получим информацию сколько дней осталось у абонента
        $clientAPI = new ClientAPI(config('services.mb_api.host'), config('services.mb_api.secret_key'));

        $searchedUsers = [];

        $this->newLine();
        $this->line('Preparing users data...');
        $bar = $this->output->createProgressBar(count($tgUsers));
        $bar->start();

        foreach ($tgUsers as $tgUser) {

            $clientAPI->setJWT($tgUser->token);

            $user = $clientAPI->getUser();
            if (isset($user['data']) and $user['data']['days_left'] == $numberDay) {

                $searchedUsers[] = $tgUser;
            }

            $bar->advance();
        }
        $bar->finish();

        $this->newLine();
        $this->info("User data preparation completed successfully!");

        // Печать таблицы
        if ($show) {
            $this->newLine();

            $this->line('Found Users to notification:');
            $this->table(
                ['UID', 'Telegram ID', 'Phone'],
                $this->prepareTableData($searchedUsers)
            );
        }

        // Отправка сообщений пользователям
        if ($send) {
            $this->newLine();


            if (count($searchedUsers) > 0) {
                $this->line("Sending user notifications...");

                $bar = $this->output->createProgressBar(count($searchedUsers));
                $bar->start();

                foreach ($searchedUsers as $user) {

                    $user->notify(new BotNotification($text));
                    $bar->advance();
                }
                $bar->finish();

                $this->newLine();
                $this->info("User notification completed successfully!");
            } else {
                $this->info("The list of users for notification is empty.");
            }
        }

        return 0;
    }

    private function prepareTableData($data)
    {
        $result = [];
        foreach ($data as $row) {
            $result[] = [
                'uid'   => $row->mb_uid,
                'id'    => $row->id,
                'phone' => $row->phone,
            ];
        }

        return $result;
    }
}
