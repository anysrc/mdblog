<?php

namespace AnySrc\MarkdownBlog;

class PluginManager
{

   const PLUGINBASE = '\AnySrc\MarkdownBlog\PluginBase';

   protected $plugins;

   public function __construct(array $plugins, $type='Frontend')
   {
      if(count($plugins)<1)
      {
         throw new \Exception("Plugin list is empty");
      }

      $pluginbase = self::PLUGINBASE;
      $typebase = '\AnySrc\MarkdownBlog\\'.$type.'PluginBase';
      if(!class_exists($typebase) || !is_subclass_of($typebase, $pluginbase))
      {
         throw new \Exception($type." is not a valid type for a plugin");
      }

      $this->plugins = array();
      foreach($plugins as $plugin)
      {
         $class = '\AnySrc\MarkdownBlog\Plugin\\'.$plugin.'\\'.$type;
         if(class_exists($class))
         {
            $instance = new $class();
            if(is_a($instance, $typebase))
            {
               $this->plugins[$plugin] = $instance;
            }
            else
            {
               throw new \Exception($class." is not a plugin!");
            }
         }
      }
   }


   public function getPluginKeys()
   {
      return array_keys($this->plugins);
   }


   public function getPluginByName($name)
   {
      if(isset($this->plugins[$name]))
      {
         return $this->plugins[$name];
      }
      throw new \Exception('Plugin not found');
   }


   public function register($app)
   {
      foreach($this->plugins as $plugin)
      {
         $plugin->register($app);
      }
   }


}
