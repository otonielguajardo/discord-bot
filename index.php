<?php

require 'vendor/autoload.php';

use Dotenv\Dotenv;
use Discord\Discord;
use Discord\WebSockets\Event;
use Discord\Helpers\Collection;
use Discord\WebSockets\Intents;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$token = $_ENV['TOKEN'];
$GLOBALS["wold_channel_id"] = $_ENV['WORLD_CHANNEL_ID'];
$GLOBALS["wold_channel_mdl"] = $_ENV['WORLD_CHANNEL_MESSAGE_DAYS_LIMIT'];
$GLOBALS["wold_channel_mcl"] = $_ENV['WORLD_CHANNEL_MESSAGE_COUNT_LIMIT'];
$GLOBALS["wold_channel_throttle"] = $_ENV['WORLD_CHANNEL_THROTTLE'];
$GLOBALS['last_throttle_time'] = 0;

$discord = new Discord([
    'token' => $token,
    'intents' => Intents::getAllIntents()
]);

$discord->on('init', function (Discord $discord) {

    $discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
        if ($message->content === '!channel_id') {
            $message->channel->sendMessage($message->channel_id);
            return;
        }

        if ($message->channel_id !== $GLOBALS["wold_channel_id"]) {
            return;
        }

        if ($message->content === '!config') {
            $message->channel->sendMessage("WORLD_CHANNEL_ID: " . $GLOBALS["wold_channel_id"]);
            $message->channel->sendMessage("WORLD_CHANNEL_MESSAGE_DAYS_LIMIT: " . $GLOBALS["wold_channel_mdl"]);
            $message->channel->sendMessage("WORLD_CHANNEL_MESSAGE_COUNT_LIMIT: " . $GLOBALS["wold_channel_mcl"]);
            return;
        }

        if (strpos($message->content, 'bumi') !== false) {
            $message->react('❤️');
        }

        if (!hasBeenThrottled()) {
            return;
        };

        cleanChannelMessages($message->channel);
    });
});

function hasBeenThrottled()
{
    // Throttle: Solo permite la acción si han pasado X segundos desde la última ejecución
    $currentTime = time(); // Tiempo actual en segundos
    if ($currentTime - $GLOBALS['last_throttle_time'] < $GLOBALS["wold_channel_throttle"]) {
        return false;
    }

    $GLOBALS['last_throttle_time'] = $currentTime;
    return true;
}

function cleanChannelMessages(Channel $channel)
{
    echo '[THROTTLE RESET] LIMPIANDO MENSAJES - ' . time() . PHP_EOL;

    // delete messages older than X days
    $channel->getMessageHistory(['limit' => 100])->then(function ($messages) use ($channel) {
        $deletables = $messages->filter(function ($message) {
            $days = 60 * 60 * 24 * $GLOBALS["wold_channel_mdl"];
            return time() - $message->timestamp->getTimestamp() > $days;
        });
        $channel->deleteMessages($deletables);
    });

    // delete messages over the limit
    $channel->getMessageHistory(['limit' => 100])->then(function ($messages) use ($channel) {
        if ($messages->count() > $GLOBALS["wold_channel_mcl"]) {
            $deletables = Collection::for(Message::class);
            $index = 0;
            foreach ($messages as $message) {
                $index++;
                if ($index > $GLOBALS["wold_channel_mcl"]) {
                    $deletables->pushItem($message);
                }
            }
            $channel->deleteMessages($deletables);
        }
    });
}

$discord->run();