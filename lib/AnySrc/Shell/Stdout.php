<?php
namespace AnySrc\Shell;

/**
 * Console output helper class
 */
class Stdout
{

   const NL = "\n";
   const MAXLINELENGTH=80;

   public static $rows;
   public static $cols;

   /**
    * Print newlines
    * @param int $i
    */
   public static function nl($i=1)
   {
      self::write(str_repeat(self::NL, $i));
   }

   /**
    * Write text with newline
    * @param string $text
    * @param bool $bbcode
    */
   public static function writel($text, $bbcode=true)
   {
      self::write($text, $bbcode);
      self::nl();
   }

   /**
    * Write text
    * @param string $text
    * @param bool $bbcode
    */
   public static function write($text, $bbcode=true)
   {
      if($bbcode===true) {
         $text = BBCode::parse($text);
      }

      echo $text;
   }

   /**
    * Write a error message
    * @param string $text
    */
   public static function err($text)
   {
      self::write("[error]".$text."[/error]", true);
   }

   /**
    * Write a error message with newline
    * @param string $text
    */
   public static function errl($text)
   {
      self::writel("[error]".$text."[/error]", true);
   }

   /**
    * Read user input from keyboard
    * @param string $text
    * @return string
    */
   public static function readprompt($text)
   {
      self::write("[head]".$text."[/head] ");
      return System::readLine();
   }

}
