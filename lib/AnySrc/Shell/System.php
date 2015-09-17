<?php

namespace AnySrc\Shell;

/**
 * Helper class for operating system specific stuff
 */
class System
{

   /**
    * Check OS is linux
    * @return bool
    */
   public static function isLinux()
   {
      return strtolower(PHP_OS)=="linux";
   }


   /**
    * Check script is running in cli mode
    * @return bool
    */
   public static function isCli()
   {
      return strtolower(php_sapi_name())=="cli";
   }


   /**
    * Execute binary and display on current tty
    * @param string $binary
    * @param string $arg1
    * @param string $arg2
    * @param string $arg3
    * @param string $arg4
    * @return int
    */
   public static function ttyenvexec($binary)
   {
      $args = func_get_args();
      unset($args[0]);

      foreach($args as &$arg)
      {
         $arg = escapeshellarg($arg);
      }
      unset($arg);

      $return = null;
      $cmd = "/usr/bin/env ".$binary." ".implode(" ", $args)."  > /dev/tty"; // `tty`
      system($cmd, $return);

      return $return;
   }


   /**
    * Get environment variable
    * @param string $name
    * @param string $default
    * @return string
    */
   public static function getEnv($name, $default=null)
   {
      if(isset($_SERVER[$name]))
      {
         return $_SERVER[$name];
      }
      return $default;
   }


   /**
    * Check for command
    * @param string $cmd
    * @return bool
    */
   public static function isCommandAvailable($cmd)
   {
      $return = null;
      system("/usr/bin/which ".escapeshellarg($cmd)." > /dev/null 2>&1", $return);
      return $return===0;
   }


   /**
    * Get first available command
    * @param array $commands
    * @return string
    */
   public static function getFirstAvailableCommand(array $commands)
   {
      foreach($commands as $command)
      {
         if(is_string($command) && !empty($command) && self::isCommandAvailable($command))
         {
            return $command;
         }
      }
      return null;
   }


   /**
    * Read one line from keyboard
    * @return string
    */
   public static function readLine()
   {
      $fh = fopen('php://stdin','r') or die($php_errormsg);
      $buffer = "";
      while(substr($buffer, -1)!="\n")
      {
          $buffer .= fgets($fh,1024);
      }
      fclose($fh);
      return trim($buffer);
   }


}
