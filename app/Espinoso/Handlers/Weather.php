<?php
namespace App\Espinoso\Handlers ; 

use \App\Espinoso\Helpers\Msg;
use Telegram\Bot\Laravel\Facades\Telegram;
use Gmopx\LaravelOWM\LaravelOWM;

class Weather extends EspinosoHandler
{
    public function shouldHandle($updates, $context=null) 
    {
        if ( ! $this->isTextMessage($updates) ) return false ; 

        return preg_match($this->regex(), $updates->message->text);
    }

    public function handle($updates, $context=null)
    {
        $day = $this->extractDay($updates->message->text);
        $date = $this->getNearestDateFromDay($day);
        $weather = $this->getWeatherForDate($date);
        $response = "el $day está pronosticado " . $weather;

        Telegram::sendMessage(Msg::plain($response)->build($updates));
    }

    private function buildMessage($response, $pattern, $updates)
    {
        if ($response instanceof Msg)
            return $response->build($updates, $pattern);
        else 
            return Msg::plain($response)->build($updates, $pattern);
    }
 
    private function regex()
    {
        return "/clima[^a-z0-9]+(?:este|el)[^a-z0-9].*(?'dia'lunes|martes|miercoles|jueves|viernes|sabado|domingo).*\??/i";
    }

    private function extractDay($text)
    {
        preg_match($this->regex(), $text, $matches);
        return $this->translateDay($matches['dia']);
    }

    private function translateDay($day)
    {
        $days = [
            'lunes'     => 'Monday',
            'martes'    => 'Tuesday',
            'miercoles' => 'Wednesday',
            'jueves'    => 'Thursday',
            'viernes'   => 'Friday',
            'sabado'    => 'Saturday' ,
            'domingo'   => 'Sunday'
        ];
        return $days[$day];
    }

    private function getNearestDateFromDay($day)
    {
        $time = strtotime("next $day");
        return \DateTime::createFromFormat('U', $time);
    }

    private function getWeatherForDate(\DateTime $date)
    {
        $owm = new LaravelOWM();
        try {
            $forecast = $owm->getWeatherForecast('Buenos Aires', "metric", "es", '', 7);
            $weather_in_day = [];
            foreach ($forecast as $weather)
            {
                if ( $this->isSameDate($date, $weather->time->day->format('Y-m-d')) )
                {
                    $weather_in_day[] = "de " . $weather->time->from->format('H:i') . " a " . $weather->time->to->format('H:i') . " " . $weather->temperature;
                }
            }
        } catch(\Exception $e)
        {
        }
        return implode(", ",$weather_in_day);
    }

    private function isSameDate(\DateTime $date, \DateTime $weather)
    {
        return $weather->format('Y-m-d') == $date->format('Y-m-d');
    }
}


