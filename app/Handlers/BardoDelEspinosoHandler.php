<?php

namespace App\Handlers;

/**
 * Class BardoDelEspinosoHandler
 * @package App\Handlers
 */
class BardoDelEspinosoHandler extends BaseCommand
{
    /**
     * @var string
     */
    protected $ignorePrefix = true;
    /**
     * @var string
     */
    protected $pattern = "send me nudes$";
    /**
     * @var string
     */
    protected $signature   = "[espi] send me nudes";
    /**
     * @var string
     */
    protected $description = "no sé, fijate";

    /**
     *
     */
    public function handle(): void
    {
        $this->espinoso->replyImage(
            'https://cdn.drawception.com/images/panels/2012/4-4/FErsE1a6t7-8.png',
            'Acá tenés tu nude, hijo de puta!'
        );
    }
}
