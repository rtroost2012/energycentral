<?php

use Composer\Script\Event;

class Composerscript
{

    public static function postUpdate(Event $event)
    {
        // Send the same command as when installing;
        self::postInstall($event);
    }

    public static function postInstall(Event $event)
    {
        $composer = $event->getComposer();
        $io = $event->getIO();

        $io->write( "Copying images..." );
        `cp vendor/twbs/bootstrap-zip/img/* web/img/`;
        `cp vendor/ivaynberg/select2/select2-spinner.gif web/img/`;
        `cp vendor/ivaynberg/select2/select2.png web/img/`;

        $io->write( "Copying css..." );
        `cp vendor/twbs/bootstrap-zip/css/* web/css/`;
        `cp vendor/ivaynberg/select2/select2.css web/css/`;

        $io->write( "Copying js..." );
        `cp vendor/twbs/bootstrap-zip/js/* web/js/`;
        `cp vendor/jquery/jquery/* web/js/`;
        `cp vendor/ivaynberg/select2/select2.js web/js/`;
        `cp -r vendor/highcharts/Highcharts-3.0.10/js/* web/js/highcharts/`;
        `cp -r vendor/highcharts/js/* web/js/highcharts/`;
    }

}
