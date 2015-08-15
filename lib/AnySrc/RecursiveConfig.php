<?php

namespace AnySrc;
use Symfony\Component\Yaml\Yaml;

class RecursiveConfig
{

   private $base;
   private $config;
   private $defaults;

   public function __construct($base, $config, $defaults=array())
   {
      if(strpos($config, $base)!==0)
      {
         throw new \Exception("config not in base");
      }

      if(substr($config, -4) !== ".yml")
      {
         throw new \Exception("config is not a yaml file");
      }

      $this->base = rtrim($base, DIRECTORY_SEPARATOR);
      $this->config = $config;
      $this->defaults = $defaults;
   }

   protected function getParent($path)
   {
      if(is_string($path) && !empty($path))
      {
         $path = rtrim($path, DIRECTORY_SEPARATOR);
         $pos = strrpos($path, DIRECTORY_SEPARATOR);
         if($pos!==false && $pos>0)
         {
            return substr($path, 0, $pos);
         }
      }
      return null;
   }

   public function parse($cfg, $result=array())
   {
      if(is_file($cfg))
      {
         $result[] = Yaml::parse(file_get_contents($cfg));
      }
      $parent = $this->getParent(dirname($cfg));
      if($parent!==null && strpos($parent, $this->base)===0)
      {
         $result = $this->parse($parent.DIRECTORY_SEPARATOR.basename($cfg), $result);
      }
      return $result;
   }

   public function getConfig()
   {
      $results = $this->parse($this->config);
      $results = array_reverse($results);
      $tmp = $this->defaults;

      foreach($results as $result)
      {
         $tmp = array_replace_recursive($tmp, $result);
      }
      return $tmp;
   }

   public function getPath($path, $default=null)
   {
      return ArrayPath::getPath($this->getConfig(), $path, $default);
   }


}
