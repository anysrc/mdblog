<?php

namespace AnySrc\MarkdownBlog;

abstract class FrontendPluginBase extends \AnySrc\MarkdownBlog\PluginBase implements \Silex\ControllerProviderInterface
{

   /**
    * @var \AnySrc\MyApplication
    */
   private $app;


   public function getPrefix()
   {
      return "/plugin/".$this->getPluginKey()."/";
   }


   public function connect(\Silex\Application $app)
   {
      $this->app = $app;

      $object = new \ReflectionClass(get_called_class());
      $tpldir = dirname($object->getFileName()).DIRECTORY_SEPARATOR."view";
      if(is_dir($tpldir) && isset($app['twig.loader']))
      {
         $app['twig.loader']->addPath($tpldir, 'plugin');
      }

      $controllers = $app['controllers_factory'];
      $this->registerRoutes($controllers);
      return $controllers;
   }


   protected function getApp()
   {
      return $this->app;
   }


   public function register(\AnySrc\MyApplication $app)
   {
      $app->mount($this->getPrefix(), $this);
   }


   public function registerRoutes(\Silex\ControllerCollection $collection)
   {
   }

}
