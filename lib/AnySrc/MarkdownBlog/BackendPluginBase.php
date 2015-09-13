<?php

namespace AnySrc\MarkdownBlog;

abstract class BackendPluginBase extends \AnySrc\MarkdownBlog\PluginBase
{

   /**
    * @var \Symfony\Component\Console\Application
    */
   private $app;

   public function getPrefix()
   {
      return $this->getPluginKey().":";
   }

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

   abstract public function registerCommands();

}
