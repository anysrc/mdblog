<?php

namespace AnySrc\Shell;

/**
 * BBCode for console output
 */
class BbCode
{

   /**
    * Parse the bbcodes and return console color codes
    * @param string $text
    * @return string
    */
   public static function parse($text)
   {
      $tags = self::get_available_tags();

      foreach($tags as $tag) {
         $quotedtag = preg_quote($tag, "/");
         $regex = "/\[".$quotedtag."(?: (.*?))?\](.*?)\[\/".$quotedtag."\]/";
         while(preg_match($regex, $text, $matches)===1) {
            $methodname = "tag_".$tag;
            $search = $matches[0];
            $args = self::get_arguments($matches[1]);
            $replace = self::$methodname($args, $matches[2]);
            $text = str_replace($search, $replace, $text);

         }
      }

      return $text;
   }


   protected static function get_available_tags()
   {
      $tags = array();
      $methods = get_class_methods("\\AnySrc\\Shell\\BbCode");
      foreach($methods as $method) {
         $result = preg_match("/^tag_([a-z]*?)\$/", $method, $match);
         if($result===1) {
            $tags[] = $match[1];
         }
      }

      return $tags;
   }


   protected static function get_arguments($text)
   {
      $args = array();

      $argslist = explode(" ", $text);
      foreach($argslist as $temparg) {
         $argnamevalue = explode("=", $temparg);
         if(count($argnamevalue)>1) {
            $name = trim($argnamevalue[0]);
            $value = trim($argnamevalue[1]);

            if(!empty($name) && !empty($value)) {
               $args[$name] = $value;
            }

         }
      }

      return $args;
   }


   protected static function tag_b($arguments, $text)
   {
      return Color::bold($text);
   }


   protected static function tag_u($arguments, $text)
   {
      return Color::underline($text);
   }


   protected static function tag_color($arguments, $text)
   {
      $available_colors = Color::getColorList();
      $color="black";
      $style = "normal";
      $bgcolor=null;
      if(isset($arguments['fg']) && in_array($arguments['fg'], $available_colors)) {
         $color = $arguments['fg'];
      }
      if(isset($arguments['bg']) && in_array($arguments['bg'], $available_colors)) {
         $color = $arguments['bg'];
      }
      if(isset($arguments['style']) && in_array($arguments['style'], array("normal", "bold", "underline"))) {
         $style = $arguments['style'];
      }

      return Color::$style($text, $color, $bgcolor);
   }

   protected static function tag_head($arguments, $text)
   {
      return Color::normal($text, "green");
   }

   protected static function tag_error($arguments, $text)
   {
      return Color::normal($text, "red");
   }

   protected static function tag_highlight($arguments, $text)
   {
      return Color::normal($text, "yellow");
   }

   protected static function tag_file($arguments, $text)
   {
      return Color::normal($text, "purple");
   }

   protected static function tag_cmd($arguments, $text)
   {
      return Color::normal($text, "cyan");
   }


}

