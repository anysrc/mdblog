<?php

namespace AnySrc\MarkdownBlog;

abstract class PluginBase
{

   public function getPluginKey()
   {
      $arr = explode("\\", get_called_class());
      return trim(strtolower($arr[count($arr)-2]));
   }

   abstract public function getPrefix();

}
