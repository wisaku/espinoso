<?php

namespace Tests\Handlers;

use Mockery;
use App\Facades\GoutteClient;
use App\Handlers\CinemaHandler;
use Spatie\Emoji\Emoji;
use Symfony\Component\DomCrawler\Crawler;

class CinemaHandlerTest extends HandlersTestCase
{
    /**
     * @test
     */
    public function it_should_handle_when_match_regex()
    {
        // Arrange
        $handler = $this->makeHandler();
        $updates = [
            $this->makeMessage(['text' => 'espi cine']),
            $this->makeMessage(['text' => 'espinoso cine?']),
            $this->makeMessage(['text' => 'espi cine??']),
            $this->makeMessage(['text' => 'espi cine!']),
            $this->makeMessage(['text' => 'espi cine!!!']),
            $this->makeMessage(['text' => 'espi ¿cine?']),
            $this->makeMessage(['text' => 'espi que hay en el cine']),
            $this->makeMessage(['text' => 'espi que hay en el cine?']),
        ];

        // Act & Assert
        collect($updates)->each(function ($update) use ($handler) {
            $this->assertTrue($handler->shouldHandle($update));
        });
    }

    /**
     * @test
     */
    public function it_should_not_handle_when_receives_another_text()
    {
        // Arrange
        $handler = $this->makeHandler();
        $updates = [
            $this->makeMessage(['text' => 'cinema']),
            $this->makeMessage(['text' => 'ig lacosacine']),
            $this->makeMessage(['text' => 'vamos al cine?']),
            $this->makeMessage(['text' => 'vamos al cine espi']),
            $this->makeMessage(['text' => 'vamos al cine, espi']),
            $this->makeMessage(['text' => 'vamos al cine, espi?']),
        ];

        // Act & Assert
        collect($updates)->each(function ($update) use ($handler) {
            $this->assertFalse($handler->shouldHandle($update));
        });
    }

    /**
     * @test
     */
    public function it_handle_and_return_movies()
    {
        $emoji = EMOJI::cinema();
        // Mocking

        $text = "{$emoji} ¿La pensás poner?\n¡Mete Netflix pelotud@, es mas barato!\nPero igual podes ver todas estas:\n\n";
        $this->espinoso->shouldReceive('replyDisablingPagePreview')->once()->with($text);

        $crawler = Mockery::mock(Crawler::class);
        $crawler->shouldReceive('filter')->andReturnSelf();
        $crawler->shouldReceive('each')->andReturn([]);
        GoutteClient::shouldReceive('request')
            ->withArgs(['GET', config('espinoso.url.cinema')])
            ->andReturn($crawler);

        // Act
        $handler = $this->makeHandler();
        $update = $this->makeMessage(['text' => 'espi cine']);
        $handler->handle($update);
        $this->assertTrue(true);
    }

    /**
     * @return CinemaHandler
     */
    protected function makeHandler(): CinemaHandler
    {
        return new CinemaHandler($this->espinoso);
    }
}
