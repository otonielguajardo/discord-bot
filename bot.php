<?php

use Discord\Discord;
use Discord\WebSockets\Event;
use Discord\Helpers\Collection;
use Discord\WebSockets\Intents;
use Discord\Parts\Channel\Message;

$token = $_ENV['TOKEN'];
$GLOBALS["channel_id"] = $_ENV['WORLD_CHANNEL_ID'];
$GLOBALS["channel_mdl"] = $_ENV['WORLD_CHANNEL_MESSAGE_DAYS_LIMIT'];
$GLOBALS["channel_mcl"] = $_ENV['WORLD_CHANNEL_MESSAGE_COUNT_LIMIT'];
$GLOBALS["channel_clean_throttle"] = $_ENV['WORLD_CHANNEL_THROTTLE'];
$GLOBALS['channel_last_clean_throttle'] = 0;

$discord = new Discord([
    'token' => $token,
    'intents' => Intents::getAllIntents(),
    'loop' => $loop
]);

$discord->on('init', function (Discord $discord) {
    echo "Bot {$discord->user->id} ready!", PHP_EOL;

    $discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
        echo "{$message->author->username}: {$message->content}", PHP_EOL;

        if ($message->author->bot) {
            return;
        }

        cleanWorldChannelMessages($discord);
        reactToBumi($message);
        reactToPing($discord, $message);
    });
});

function genScaryChars($longitud = 10, $ranges = [[33, 47], [58, 64], [91, 96], [123, 126]])
{
    $sequence = '';
    $permittedChars = [];
    foreach ($ranges as $range) {
        $permittedChars = array_merge($permittedChars, range($range[0], $range[1]));
    }
    for ($i = 0; $i < $longitud; $i++) {
        $ascii = $permittedChars[array_rand($permittedChars)];
        $sequence .= chr($ascii);
    }
    return $sequence;
}

function reactToPing(Discord $discord, Message $message)
{
    $content = strtolower($message->content);

    $bot_pinged = strpos($content, '<@' . $discord->user->id . '>') !== false;

    if (!$bot_pinged) {
        return false;
    }

    if (strpos($content, 'channel_id') !== false) {
        $message->reply($message->channel_id);
    }

    if (strpos($content, 'hola') !== false) {
        $message->channel->sendMessage(genScaryChars());
    }

    if (rand(1, 10) === 1) {
        $loops = rand(1, 4);
        for ($i = 0; $i < $loops; $i++) {
            $message->channel->sendMessage(genScaryChars());
        }
    }
}

function reactToBumi(Message $message)
{
    $content = strtolower($message->content);
    if (strpos($content, 'bumi') !== false) {
        $message->react('❤️');
    }
}

function cleanWorldChannelMessages(Discord $discord)
{
    // throttle clean request
    $currentTime = time();
    if ($currentTime - $GLOBALS['channel_last_clean_throttle'] < $GLOBALS["channel_clean_throttle"]) {
        return false;
    }
    $GLOBALS['channel_last_clean_throttle'] = $currentTime;
    echo '[THROTTLE RESET] LIMPIANDO MENSAJES - ' . time() . PHP_EOL;

    // select channel to clean
    $channel = $discord->getChannel($GLOBALS["channel_id"]);
    if ($channel === null) {
        echo 'Channel not found' . PHP_EOL;
        return;
    }

    // delete messages older than X days
    $channel->getMessageHistory(['limit' => 100])->then(function ($messages) use ($channel) {
        $deletables = $messages->filter(function ($message) {
            $days = 60 * 60 * 24 * $GLOBALS["channel_mdl"];
            return time() - $message->timestamp->getTimestamp() > $days;
        });
        $channel->deleteMessages($deletables);
    });

    // delete messages over the limit
    $channel->getMessageHistory(['limit' => 100])->then(function ($messages) use ($channel) {
        if ($messages->count() > $GLOBALS["channel_mcl"]) {
            $deletables = Collection::for(Message::class);
            $index = 0;
            foreach ($messages as $message) {
                $index++;
                if ($index > $GLOBALS["channel_mcl"]) {
                    $deletables->pushItem($message);
                }
            }
            $channel->deleteMessages($deletables);
        }
    });
}