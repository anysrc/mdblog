<?php

namespace AnySrc\MarkdownBlog;

/**
 * Base for frontend extension
 */
abstract class FrontendPluginBase extends \AnySrc\MarkdownBlog\PluginBase implements \Silex\ControllerProviderInterface
{

   /**
    * @var \AnySrc\MyApplication
    */
   private $app;


   /**
    * Prefix for a symfony route
    * @return string
    */
   public function getPrefix()
   {
      return "/plugin/".$this->getPluginKey()."/";
   }


   /**
    * Connect this plugin to silex application
    * @param \Silex\Application $app
    * @return \Silex\ControllerCollection
    */
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


   /**
    * Get the silex app
    * @return \Silex\Application
    */
   protected function getApp()
   {
      return $this->app;
   }


   /**
    * Register this plugin to silex application
    * @param \AnySrc\MyApplication $app
    */
   public function register(\AnySrc\MyApplication $app)
   {
      $app->mount($this->getPrefix(), $this);
   }


   /**
    * Define the plugin routes
    * @param \Silex\ControllerCollection $collection
    */
   public function registerRoutes(\Silex\ControllerCollection $collection)
   {
   }

}
