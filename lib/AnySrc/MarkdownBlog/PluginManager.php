<?php

namespace AnySrc\MarkdownBlog;

/**
 * The plugin manager
 */
class PluginManager
{

   /**
    * Plugin base class
    */
   const PLUGINBASE = '\AnySrc\MarkdownBlog\PluginBase';

   /**
    * All plugin instances
    * @var array
    */
   protected $plugins;


   /**
    * Initialize the plugin manager instance
    * @param string[] $plugins Array of all plugin keys
    * @param string $type "Frontend" or "Backend"
    * @throws \Exception
    */
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


   /**
    * Array of all plugin keys
    * @return string[]
    */
   public function getPluginKeys()
   {
      return array_keys($this->plugins);
   }


   /**
    * Get plugin by key name
    * @param string $name
    * @return \AnySrc\MarkdownBlog\PluginBase
    * @throws \Exception
    */
   public function getPluginByName($name)
   {
      if(isset($this->plugins[$name]))
      {
         return $this->plugins[$name];
      }
      throw new \Exception('Plugin not found');
   }


   /**
    * Register the plugins
    * @param object $app The symfony console or silex application
    */
   public function register($app)
   {
      foreach($this->plugins as $plugin)
      {
         $plugin->register($app);
      }
   }


}
