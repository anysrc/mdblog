<?php

namespace AnySrc;

class SearchPropertyParser
{

   const MULTIWORDCHAR='"';
   const PROPCHAR=":";
   const SEPARATORS="\n\r\t ";

   protected $str;
   protected $parameters;


   /**
    * Constructor
    * @param string $str
    * @param int $maxlength
    */
   public function __construct($str, $maxlength=1014)
   {
      $this->str = $str;
      if(strlen($str)>$maxlength)
      {
         $this->str = substr($this->str, 0, $maxlength);
      }
      $this->parameters = $this->parse($this->str);
   }


   /**
    * Parse string
    * @param string $str
    * @return array
    */
   protected function parse($str)
   {
      $str = trim($str);

      $result = array();
      $multiword = false;

      $property=null;
      $temp = "";
      for($i=0; $i<strlen($str); $i++)
      {
         $char = $str[$i];
         $nextchar = (isset($str[($i+1)]) ? $str[($i+1)] : null);

         if($char===self::MULTIWORDCHAR && $nextchar===self::MULTIWORDCHAR)
         {
            $i++;
         }
         elseif($char===self::MULTIWORDCHAR && $nextchar!==self::MULTIWORDCHAR)
         {
            $multiword=!$multiword;
            continue;
         }

         if($multiword===false && $char===self::PROPCHAR)
         {
            $property=$temp;
            $temp="";
         }
         else if(strpos(self::SEPARATORS, $char)===false || $multiword===true)
         {
            $temp.=$char;
         }
         else
         {
            $result[] = array("property"=>$property, "value"=>$temp);
            $property=null;
            $temp="";
         }
      }

      if(!empty($property) || !empty($temp))
      {
         $result[] = array("property"=>$property, "value"=>$temp);
      }

      return $result;
   }


   /**
    * Get all parsed parameters
    * @return array
    */
   public function getAll()
   {
      return $this->parameters;
   }


   /**
    * Get all parameter by property name
    * @param string $property
    * @return array
    */
   public function getByProperty($property)
   {
      $result = array();

      foreach($this->parameters as $param)
      {
         if($param['property']===$property)
         {
            $result[] = $param['value'];
         }
      }

      return $result;
   }


}
