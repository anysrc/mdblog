<?php

namespace AnySrc\MarkdownBlog;

abstract class PluginBase implements \Silex\ControllerProviderInterface
{

   /**
    * @var \AnySrc\MyApplication
    */
   private $app;


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
      $app->mount($this->getRoutePrefix(), $this);
   }


   public function getPluginKey()
   {
      $arr = explode("\\", get_called_class());
      return trim(strtolower($arr[count($arr)-2]));
   }


   public function getRoutePrefix()
   {
      return "/plugin/".$this->getPluginKey()."/";
   }


   public function registerRoutes(\Silex\ControllerCollection $collection)
   {
   }


}
