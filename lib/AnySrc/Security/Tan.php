<?php

namespace AnySrc\Security;

class Tan extends \AnySrc\YamlFile
{


   public function __construct($file)
   {
      $this->file = $file;
      $this->cleanTimedout();
   }


   /**
    * Generate new tan
    * @param int $length
    * @return string
    */
   public static function newTan($length=18)
   {
      $hash = hash('sha512', "-".mt_rand(1, 999999)."-".microtime(true)."-");
      $max = strlen($hash)-$length;
      $start = mt_rand(0, $max);
      $tan = substr($hash, $start, $length);
      return $tan;
   }


   /**
    * Hash a string
    * @param string $s
    * @return string
    */
   public static function calcHash($s)
   {
      return hash('sha256', $s);
   }


   /**
    * Get Timeout as date string
    * @return string
    */
   public static function getTimeout()
   {
      return date("c", time()+7200);
   }


   /**
    * Skeleton for token YAML file
    * @return type
    */
   public static function getSkeleton()
   {
      return array(
          "tans" => array(),
          "sessions" => array(),
      );
   }


   /**
    * Get tan or session
    * @param string $what
    * @return array
    */
   protected function getTanOrSession($what="tans")
   {
      $tan = self::newTan();
      $sectan = self::calcHash($tan);

      $field = array(
          "timeout" => self::getTimeout()
      );

      $data = $this->load();
      $data[$what][$sectan] = $field;
      $this->save($data);

      return $tan;
   }


   /**
    * Create new login tan
    * @return string
    */
   public function newLoginTan()
   {
      return $this->getTanOrSession("tans");
   }


   /**
    * Create new session tan
    * @return string
    */
   public function newSessionTan()
   {
      return $this->getTanOrSession("sessions");
   }


   /**
    * Clear all
    */
   public function clear()
   {
      $this->save(self::getSkeleton());
   }


   /**
    * Clear all with expired timeout
    */
   public function cleanTimedout()
   {
      $data = $this->load();
      foreach(array("tans", "sessions") as $what)
      {
         foreach($data[$what] as $key => $tan)
         {
            if(strtotime($tan['timeout'])<time())
            {
               unset($data[$what][$key]);
            }
         }
         unset($tan);
      }
      $this->save($data);
   }


   /**
    * Tan or session exists
    * @param string $tan
    * @param string $what
    * @return bool
    */
   protected function isTanOrSessionExists($tan, $what="tans")
   {
      $data = $this->load();
      $h = self::calcHash($tan);
      return isset($data[$what][$h]);
   }


   /**
    * Is Tan exists
    * @param string $tan
    * @return bool
    */
   public function isTanExists($tan)
   {
      return $this->isTanOrSessionExists($tan, "tans");
   }


   /**
    * Is Session exists
    * @param string $tan
    * @return bool
    */
   public function isSessionExists($tan)
   {
      return $this->isTanOrSessionExists($tan, "sessions");
   }


   /**
    * Get session
    * @param string $tan
    * @return array
    */
   public function getSession($tan)
   {
      $data = $this->load();
      $tansec = self::calcHash($tan);
      if(isset($data['sessions'][$tansec]))
      {
         return $data['sessions'][$tansec];
      }
      return null;
   }


   /**
    * Delete tan or session
    * @param string $tan
    * @param string $what
    */
   protected function deleteTanOrSession($tan, $what="tans")
   {
      $data = $this->load();
      $h = self::calcHash($tan);
      if($this->isTanOrSessionExists($tan, $what))
      {
         unset($data[$what][$h]);
      }
      $this->save($data);
   }


   /**
    * Delete tan
    * @param string $tan
    */
   public function deleteTan($tan)
   {
      $this->deleteTanOrSession($tan, "tans");
   }


   /**
    * Delete session
    * @param string $tan
    */
   public function deleteSession($tan)
   {
      $this->deleteTanOrSession($tan, "sessions");
   }


   /**
    * Do login
    * @param string $tan
    * @return string
    */
   public function login($tan)
   {
      if($this->isTanExists($tan))
      {
         $this->deleteTan($tan);

         $stan = $this->newSessionTan();
         $stansec = self::calcHash($stan);

         $data = $this->load();
         $data['sessions'][$stansec]['ip'] = $_SERVER['REMOTE_ADDR'];
         $data['sessions'][$stansec]['agent'] = $_SERVER['HTTP_USER_AGENT'];
         $data['sessions'][$stansec]['tan'] = $tan;
         $this->save($data);

         return $stan;
      }
      return null;
   }


   /**
    * Is logged in
    * @param string $tan
    * @return bool
    */
   public function isLogin($tan)
   {
      return $this->isSessionExists($tan);
   }


   /**
    * Perform logout
    * @param string $tan
    */
   public function logout($tan)
   {
      $this->deleteSession($tan);
   }


   /**
    * Extend session timeout
    * @param string $tan
    */
   public function extendSessionTimeout($tan)
   {
      if($this->isSessionExists($tan))
      {
         $data = $this->load();
         $h = self::calcHash($tan);
         $data['sessions'][$h]['timeout'] = self::getTimeout();
         $this->save($data);
      }
   }


   /**
    * Get all sessions
    * @return array
    */
   public function getSessions()
   {
      $data = $this->load();
      return $data['sessions'];
   }


   /**
    * Get all tans
    * @return array
    */
   public function getTans()
   {
      $data = $this->load();
      return $data['tans'];
   }

}
