<?php

namespace AnySrc\MarkdownBlog;

/**
 * Base for a backend extension
 */
abstract class BackendPluginBase extends \AnySrc\MarkdownBlog\PluginBase
{

   /**
    * @var \Symfony\Component\Console\Application
    */
   private $app;

   /**
    * Praefix for a command
    * @return string
    */
   public function getPrefix()
   {
      return $this->getPluginKey().":";
   }

   /**
    * Register this plugin in cmd
    * @param \Symfony\Component\Console\Application $app
    */
   public function register(\Symfony\Component\Console\Application $app)
   {
      $this->app = $app;
      $this->registerCommands();
   }

   /**
    *
    * @param string $name
    * @return \Symfony\Component\Console\Command\Command
    */
   public function createCommand($name)
   {
      return $this->app->register($this->getPrefix().$name);
   }

   /**
    * Create the plugin commands
    */
   abstract public function registerCommands();

}
