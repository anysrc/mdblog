<?php

namespace AnySrc;

/**
 * Silex application with traits
 */
class MyApplication  extends \Silex\Application
{

   use \Silex\Application\TwigTrait;
   use \Silex\Application\UrlGeneratorTrait;

}
