<?php

namespace AnySrc;

/**
 * Levensthtein search
 */
class Levenshtein
{

   const MAXLENGTH=255;
   const SPLITRGX="/[\s,\._\-]+/";


   /**
    * Compare a text with multiple patterns and return the avg score
    * @param string $text
    * @param array $patterns
    * @return float
    */
   public static function compareText($text, array $patterns)
   {
      if(count($patterns)<1)
      {
         return null;
      }

      $patternlist = array();
      foreach($patterns as $pattern)
      {
         $temp = preg_split(self::SPLITRGX, strtolower($pattern));
         $patternlist = array_merge($patternlist, $temp);
      }

      $wordlist = preg_split(self::SPLITRGX, strtolower($text));
      $results = array();
      foreach($patternlist as $patternitem)
      {
         if(strlen($patternitem)>self::MAXLENGTH)
         {
            $patternitem=substr($patternitem, 0, self::MAXLENGTH);
         }

         if(!isset($results[$patternitem]))
         {
            $results[$patternitem]=PHP_INT_MAX;
         }

         foreach($wordlist as $worditem)
         {
            if(strlen($worditem)>self::MAXLENGTH)
            {
               $worditem=substr($worditem, 0, self::MAXLENGTH);
            }

            $temp = levenshtein($patternitem, $worditem);
            if($temp<$results[$patternitem])
            {
               $results[$patternitem] = $temp;
            }

            if($temp<1)
            {
               break;
            }
         }
      }

      $score = (array_sum($results)/count($results));
      return $score;
   }


   /**
    * Compare multiple texts with its patterns and return the avg score
    * array(
    *    array("text"=>"...", "patterns"=>array("foo", "bar"),
    *    ...
    *    ...
    * )
    *
    * @param array $comparisons
    * @return float
    */
   public static function compareTextMultiple(array $comparisons)
   {
      $results = array();
      foreach($comparisons as $cmp)
      {
         $temp = self::compareText($cmp['text'], $cmp['patterns']);
         if($temp!==null)
         {
            $results[] = $temp;
         }
      }

      if(count($results)<1)
      {
         return 0;
      }

      $score = (array_sum($results)/count($results));
      return $score;
   }


}
