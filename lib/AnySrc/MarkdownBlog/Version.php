<?php

namespace AnySrc\MarkdownBlog;

class Version
{

   private static $version = null;

   /**
    * Return current version code
    * @return string
    */
   public static function getVersion()
   {
      if(self::$version===null && is_file(DIR_ABSOLUTEBASE."version.properties"))
      {
         $content = file_get_contents(DIR_ABSOLUTEBASE."version.properties");
         $ini = parse_ini_string($content);
         self::$version = $ini['DATE'].".".$ini['BUILD'];
      }
      return self::$version;
   }

}
