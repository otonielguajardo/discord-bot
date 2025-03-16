<?php

use Discord\Discord;
use Discord\WebSockets\Event;
use Discord\WebSockets\Intents;
use Discord\Parts\Channel\Message;

$discord = new Discord([
    'token' => $_ENV['DISCORD_BOT_TOKEN'],
    'intents' => Intents::getAllIntents(),
    'loop' => $loop
]);

$discord->on('init', function (Discord $discord) {
    echo "Bot {$discord->user->id} ready!", PHP_EOL;

    $discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
        echo "{$message->author->username}: {$message->content}", PHP_EOL;

        // ignore if message is from bot
        if ($message->author->bot) {
            return;
        }

        // check if bot mentioned
        $content = strtolower($message->content);
        $botPinged = strpos($content, '<@' . $discord->user->id . '>') !== false;
        if ($botPinged) {
            handle($message);
        }
    });
});

function handle(Message $message)
{
    // react to mention
    $message->react('❤️');

    // random chance of replying to message
    if (rand(1, 20) === 1) {
        $loops = rand(1, 4);
        $message->reply(genRandomChars());
    }

    // random chance of participating in channel
    if (rand(1, 20) === 1) {
        $loops = rand(1, 4);
        for ($i = 0; $i < $loops; $i++) {
            $message->channel->sendMessage(genRandomChars());
        }
    }
}

function genRandomChars($longitud = 20, $ranges = [[33, 47], [58, 64], [91, 96], [123, 126]])
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