<?php

namespace AnySrc;
use Symfony\Component\Yaml\Yaml;

/**
 * YAML Parser implementation
 */
abstract class YamlFile
{

   protected $file;
   protected $cache;


   /**
    * Skeleton for token YAML file
    * @return type
    */
   public static function getSkeleton()
   {
      return array();
   }

   /**
    * Load YML config
    * @return array
    */
   public function load()
   {
      $data = self::getSkeleton();
      if(is_file($this->file))
      {
         $data = Yaml::parse(file_get_contents($this->file));
      }
      return $data;
   }


   /**
    * Cache parsed data
    * @param bool $forcereload
    * @return array
    */
   public function &loadFromCache($forcereload=false)
   {
      if($this->cache===null || $forcereload===true)
      {
         $this->cache = $this->load();
      }
      return $this->cache;
   }


   public function setCache(array $data)
   {
      $this->cache = $data;
   }


   /**
    * Save to YAML file
    * @param array $data
    */
   public function save(array $data)
   {
      $yml = Yaml::dump($data, 99, 3);
      file_put_contents($this->file, $yml);
   }


   public function saveFromCache()
   {
      $this->save($this->loadFromCache());
   }

}
