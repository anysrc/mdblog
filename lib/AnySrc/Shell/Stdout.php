<?php
namespace AnySrc\Shell;

class Stdout
{

   const NL = "\n";
   const MAXLINELENGTH=80;

   public static $rows;
   public static $cols;

   public static function nl($i=1)
   {
      self::write(str_repeat(self::NL, $i));
   }

   public static function writel($text, $bbcode=true)
   {
      self::write($text, $bbcode);
      self::nl();
   }

   public static function write($text, $bbcode=true)
   {
      if($bbcode===true) {
         $text = BBCode::parse($text);
      }

      echo $text;
   }

   public static function err($text)
   {
      self::write("[error]".$text."[/error]", true);
   }

   public static function errl($text)
   {
      self::writel("[error]".$text."[/error]", true);
   }

   public static function readprompt($text)
   {
      self::write("[head]".$text."[/head] ");
      return System::readLine();
   }

}
