<?php namespace App\Espinoso\Handlers;

use Telegram\Bot\Objects\Message;

abstract class EspinosoCommandHandler extends EspinosoHandler
{
    protected $flags = 'i';
    protected $prefix_regex = "^(?'e'espi(noso)?\s+)"; // 'espi|espinoso '
    protected $pattern = '$';
    protected $matches = [];
    /**
     * @var bool
     * If false, should match 'espi'
     * If true, could not match 'espi'
     */
    protected $allow_ignore_prefix = false;

    /**
     * Default behavior to determine is Command handler should response the message.
     *
     * @param Message $message
     * @return bool
     */
    public function shouldHandle(Message $message): bool
    {
        return $this->matchCommand($this->pattern, $message, $this->matches);
    }

    /*
     * Internals
     */

    /**
     * @param $pattern
     * @param Message $message
     * @param array|null $matches
     * @return bool
     */
    protected function matchCommand($pattern, Message $message, array &$matches = null): bool
    {
        $quantifier = $this->allow_ignore_prefix ? '{0,3}' : '{1,3}';
        $text = $message->getText();
        $pattern = "/{$this->prefix_regex}{$quantifier}{$pattern}/{$this->flags}";

        return preg_match($pattern, $text, $matches) === 1;
    }

}
