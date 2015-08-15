<?php

namespace AnySrc;

class ArrayPath
{


   /**
    * Get config item by path
    * "/some/key" --> $cfg['some']['key']
    * @param array $data
    * @param string $path
    * @param string $default
    * @return mixed
    */
   public static function getPath(array $data, $path, $default=null)
   {
      $path = trim($path, "/");
      $parts = explode("/", $path);

      $temp = $data;
      foreach($parts as $part)
      {
         if(isset($temp[$part]))
         {
            $temp = $temp[$part];
         }
         else
         {
            return $default;
         }
      }
      return $temp;
   }


   /**
    * Set value in given path
    * @param array $data
    * @param string $path
    * @param mixed $value
    * @throws \Exception
    */
   public static function setPath(array &$data, $path, $value)
   {
      $path = trim($path, "/");
      $parts = explode("/", $path);

      $i=0;
      $node = &$data;
      $newnode = null;

      foreach($parts as $item)
      {
         $i++;
         if(isset($node[$item]))
         {
            $node = &$node[$item];
         }
         elseif($i>=count($parts))
         {
            $newnode = $item;
         }
         else
         {
            throw new \Exception('Node not found');
         }
      }

      if($newnode!==null)
      {
         $node[$newnode] = $value;
      }
      else
      {
         $node = array_replace_recursive($node, $value);
      }

      unset($node);
      unset($data);
   }


}
