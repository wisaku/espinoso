<?php namespace App\Espinoso\Handlers;

use Telegram\Bot\Objects\Message;

class StickersHandler extends EspinosoCommandHandler
{
    /**
     * @var bool
     */
    protected $ignorePrefix = true;

    protected $signature   = "[espi] maybe";
    protected $description = "solo funciona para facu... los demás a comerla";


    /**
     * @var null
     */
    protected $match = null;

    public function shouldHandle(Message $message): bool
    {
        $this->message = $message;

        $this->match = collect(config('stickers.patterns'))->filter(function ($pattern) {
            return $this->message->getFrom()->getId() === $pattern['userId']
                && $this->matchCommand($pattern['pattern'], $this->message);
        });

        return $this->match->isNotEmpty();
    }

    public function handle(): void
    {
        $this->espinoso->replySticker($this->match->first()['sticker']);
    }
}
