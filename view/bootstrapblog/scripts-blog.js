
$(document).ready(function()
{

   // http://stackoverflow.com/questions/5521137/codemirror-2-highlight-only-no-editor
   $('pre').each(function() {

       var $this = $(this),
          $code = $this.html(),
          $unescaped = $('<div/>').html($code).text();

       var $ext = $this.find('code') && $this.find('code').attr('class') ? $this.find('code').attr('class').split('-')[1] : undefined;
       var $mode = CodeMirror.findModeByExtension($ext);

       if($ext && !$mode)
       {
          $mode = CodeMirror.findModeByName($ext);
       }

       if($mode && !$this.is('.inline'))
       {
          $this.empty();

          var editor = CodeMirror(this, {
             value: $unescaped,
             lineNumbers: !$this.is('.inline'),
             readOnly: true
          });

          CodeMirror.autoLoadMode(editor, $mode.mode);
          editor.setOption("mode", $mode.mode);
          editor.setSize(null, 'auto');

          if($(editor.getWrapperElement()).height()>300)
          {
             editor.setSize(null, '300px');
          }

       }

   });

});

