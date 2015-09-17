<?php

namespace AnySrc\MarkdownBlog;

/**
 * Base for frontend and backend plugins
 */
abstract class PluginBase
{

   public function getPluginKey()
   {
      $arr = explode("\\", get_called_class());
      return trim(strtolower($arr[count($arr)-2]));
   }

   /**
    * Returns the route / command prefix
    */
   abstract public function getPrefix();

}
