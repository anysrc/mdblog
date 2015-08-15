<?php
namespace AnySrc\Shell;

class Stdout
{

   const NL = "\n";

   public static $rows;
   public static $cols;

   public static function nl($i=1)
   {
      self::write(str_repeat(self::NL, $i));
   }

   public static function writel($text, $bbcode=true)
   {
      self::write($text, $bbcode);
      echo "\n";
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

   public static function pagename(\AnySrc\MarkdownBlog\Page $page)
   {
      $flags = array();
      $flags[] = $page->getIsDisabled() ? "[error]d[/error]" : "-";
      $flags[] = $page->getIsVisible() ? "-" : "[error]h[/error]";
      $flags[] = $page->getIsInPrivateFolder() ? "[error]p[/error]" : "-";

      Stdout::write("[highlight][ ::".$page->getHash()." ][/highlight] ".
         implode("", $flags)." ".
         $page->getTitle()." [file]~/".$page->getName()."[/file]");
   }

   public static function pagenamel(\AnySrc\MarkdownBlog\Page $page)
   {
      self::pagename($page);
      self::nl();
   }

}
