<?php namespace App\Espinoso;

use Exception;
use App\Facades\GuzzleClient;
use App\Espinoso\Handlers\BaseHandler;
use App\Espinoso\DeliveryServices\EspinosoDeliveryInterface;
use Telegram\Bot\Objects\Chat;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\User as UserObject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Class Espinoso
 * @package Espinoso
 */
class Espinoso
{
    /**
     * @var array
     */
    protected $handlers;
    /**
     * @var EspinosoDeliveryInterface
     */
    protected $delivery;
    /**
     * @var Message
     */
    protected $message;

    public function __construct(Collection $handlers)
    {
        $this->handlers = $handlers->map(function ($handler) {
            return new $handler($this);
        });
    }

    /**
     * @param Message $message
     */
    public function executeHandlers(Message $message)
    {
        $this->message = $message;

        $this->getHandlers()->filter(function (BaseHandler $handler) {
            return $handler->shouldHandle($this->message);
        })->each(function (BaseHandler $handler) {
            try {
                $handler->handle($this->message);
            } catch (Exception $e) {
                $handler->handleError($e, $this->message);
            }
        });
    }

    /**
     * @param int $chatId
     * @param string $text
     * @param string $format
     * @param array $options
     */
    public function sendMessage(int $chatId, string $text, string $format = 'Markdown', array $options = []): void
    {
        $params = array_merge($options, [
            'chat_id' => $chatId,
            'text'    => $text,
            'parse_mode' => $format
        ]);

        $this->delivery->sendMessage($params);
    }

    /**
     * @param string $text
     * @param string $format
     * @param array $options
     */
    public function reply(string $text, string $format = 'Markdown', array $options = []): void
    {
        $this->sendMessage($this->message->getChat()->getId(), $text, $format, $options);
    }

    /**
     * @param string $url
     * @param string $caption
     * @param array $options
     */
    public function replyImage(string $url, string $caption = '', array $options = []): void
    {
        $params = array_merge($options, [
            'chat_id' => $this->message->getChat()->getId(),
            'photo'   => $url,
            'caption' => $caption
        ]);

        $this->delivery->sendImage($params);
    }

    /**
     * @param string $sticker
     * @param array $options
     */
    public function replySticker(string $sticker, array $options = []): void
    {
        $params = array_merge($options, [
            'chat_id' => $this->message->getChat()->getId(),
            'sticker' => $sticker,
        ]);

        $this->delivery->sendSticker($params);
    }

    public function replyGif(string $gif, array $options = []): void
    {
        $params = array_merge($options, [
            'chat_id'  => $this->message->getChat()->getId(),
            'document' => $gif,
        ]);

        $this->delivery->sendGif($params);
    }

    /**
     * @param EspinosoDeliveryInterface $delivery
     */
    public function setDelivery(EspinosoDeliveryInterface $delivery)
    {
        $this->delivery = $delivery;
    }

    public function isMe(UserObject $user)
    {
        return $this->delivery->isMe($user);
    }

    /**
     * @return Collection
     */
    public function getHandlers(): Collection
    {
        return $this->handlers;
    }

    public function transcribe(Message $message)
    {
        $voice   = $message->getVoice();
        $file_id = $voice->getFileId();
        $stream  = $this->delivery->getVoiceStream($voice);

        // Save as ogg (Telegram audio format)
        // and convert it to wav (Voice format required)
        Storage::put("{$file_id}.ogg", $stream->getContents());
        $fileIn  = storage_path("app/{$file_id}.ogg");
        $fileOut = storage_path("app/{$file_id}.wav");
        @exec("ffmpeg -y -i {$fileIn} {$fileOut} 2> /dev/null");
        $audio = Storage::get("{$file_id}.wav");
        @exec("rm -f {$fileIn} 2> /dev/null");
        @exec("rm -f {$fileOut} 2> /dev/null");

        // Get transcription
        $response = GuzzleClient::post(config('espinoso.voice.url'), [
            'headers' => [
                'Authorization' => "Bearer " . config('espinoso.voice.token'),
                'Content-Type' => 'audio/wav'
            ],
            'body' => $audio
        ]);

        $data = json_decode($response->getBody());

        return $data->_text;
    }

    /**
     * @param Chat $chat
     * @return bool
     */
    public function registerChat(Chat $chat)
    {
        return $this->delivery->registerChat($chat);
    }

    /**
     * @param Chat $chat
     */
    public function deleteChat(Chat $chat): void
    {
        $this->delivery->deleteChat($chat);
    }

    public function checkIfHasRegisteredChat(Chat $chat): void
    {
        if (!$this->hasRegisteredChat($chat)) {
            $this->sendMessage($chat->getId(), trans('messages.chat.set-start'));
        }
    }

    protected function hasRegisteredChat(Chat $chat): bool
    {
        return $this->delivery->hasRegisteredChat($chat);
    }
}
