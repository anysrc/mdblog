$(document).ready(function()
{

   window.setTimeout(function()
   {
      if(/^#[0-9]+,[0-9]+$/.test(location.hash))
      {
         var parts = location.hash.substring(1).split(',');
         location.hash="";
         $(document).scrollTop(parseInt(parts[0]));
         $(document).scrollLeft(parseInt(parts[1]));
      }
   }, 100);

   var checkinterval = function()
   {
      var url = Config.jsonismodifiedurl+"?name="+Config.currentpagename+"&time="+Config.currentpageeditdate;
      $.getJSON(url, {}, proccess);
   }

   var proccess = function(data)
   {
      if(data.success && data.result)
      {
         location.hash = "#"+$(document).scrollTop()+","+$(document).scrollLeft();
         location.reload();
      }
   }

   var initbyjsoninfo = function(data)
   {
      if(!data.success)
      {
         return;
      }

      var result = data.result;
      if(result.loginstatus==true &&
         result.realtimeeditor && 
         result.realtimeeditor.enabled==true)
      {
         var interval = 5;
         if(result.realtimeeditor.interval && result.realtimeeditor.interval>=1)
         {
            interval = result.realtimeeditor.interval;
         }
         window.setInterval(checkinterval, interval*1000);
      }
   }
   
   $.getJSON(Config.jsoninfourl, {}, initbyjsoninfo);

});

