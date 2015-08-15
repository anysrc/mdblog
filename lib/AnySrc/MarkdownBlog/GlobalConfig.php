<?php

namespace AnySrc\MarkdownBlog;
use Symfony\Component\Yaml\Yaml;

class GlobalConfig
{

   private $cfg;
   private $mergemode;


   /**
    * Load initial YAML file
    * @param string $file
    */
   public function __construct($mergrecursive=true)
   {
      $this->cfg = array();

      if($mergrecursive===true)
      {
         $this->mergemode="array_replace_recursive";
      }
      else
      {
         $this->mergemode="array_replace";
      }
   }


   /**
    * Add YAML file to config array
    * @param string $file YAML File
    * @param string $key File Key
    * @return \AnySrc\MarkdownBlog\GlobalConfig
    */
   public function loadFile($file, $key)
   {
      $data = Yaml::parse(file_get_contents($file));
      $this->cfg[$key] = array(
          "file" => $file,
          "data" => $data,
          "changed" => false,
      );
      return $this;
   }


   /**
    * Save data to yml
    * @param string $key
    * @throws \Exception
    */
   public function saveFile($key)
   {
      if(!isset($this->cfg[$key]))
      {
         throw new \Exception("Key not exist");
      }
      $content = Yaml::dump($this->cfg[$key]['data'], 999, 3);
      file_put_contents($this->cfg[$key]['file'], $content);
   }


   /**
    * Save all files where changed=true
    */
   public function saveChanges()
   {
      foreach($this->cfg as $key => $file)
      {
         if($file['changed']===true)
         {
            $this->saveFile($key);
            $this->cfg[$key]['changed']=false;
         }
      }
   }


   /**
    * Get complete config array
    * @return array
    */
   public function toArray()
   {
      return $this->cfg;
   }


   /**
    * Get data of given file key
    * @param string $key
    * @return mixed
    * @throws \Exception
    */
   public function getFileData($key)
   {
      if(!isset($this->cfg[$key]))
      {
         throw new \Exception("Key not exist");
      }
      return $this->cfg[$key]['data'];
   }


   /**
    * Merge all config files
    * @return array
    */
   public function toMergedArray()
   {
      $temp = array();
      foreach($this->cfg as $file)
      {
         $mm = $this->mergemode;
         $temp = $mm($temp, $file['data']);
      }
      return $temp;
   }


   /**
    * Get config item by path
    * "/some/key" --> $cfg['some']['key']
    * @param string $path
    * @param string $default
    * @return mixed
    */
   public function getPath($path, $default=null, $key=null)
   {
      $temp = null;
      if($key===null)
      {
         $temp = $this->toMergedArray();
      }
      else
      {
         $temp = $this->getFileData($key);
      }

      return \AnySrc\ArrayPath::getPath($temp, $path, $default);
   }


   /**
    * Set value in given path
    * @param string $key
    * @param string $path
    * @param mixed $value
    * @throws \Exception
    */
   public function setPath($key, $path, $value)
   {
      if(!isset($this->cfg[$key]))
      {
         throw new \Exception("Key not exist");
      }

      \AnySrc\ArrayPath::setPath($this->cfg[$key]['data'], $path, $value);
      $this->cfg[$key]['changed'] = true;
   }


   /**
    * Add item to a array
    * @param string $key
    * @param string $path
    * @param mixed $item
    * @param mixed $nodename if not null, this is the array key
    */
   public function appendArray($key, $path, $item, $nodename=null)
   {
      $node = $this->getPath($path, null, $key);
      if(!is_array($node))
      {
         $node = array();
      }

      if($nodename===null)
      {
         $node[] = $item;
      }
      else
      {
         $node[$nodename] = $item;
      }

      $this->setPath($key, $path, $node);
   }


}
